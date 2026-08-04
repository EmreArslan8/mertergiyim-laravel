<?php

namespace App\Console\Commands;

use App\Models\TelegramAccount;
use danog\MadelineProto\API;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Hesaplı çekim (MTProto) bu sunucuda mümkün mü?
 *
 * MadelineProto paylaşımlı hostinglerde iki yerde takılıyor: bellek sınırı ve
 * Telegram sunucularına kapalı giden bağlantı. İkisi de kütüphane kurulmadan
 * ölçülebiliyor — bu yüzden komut önce çalıştırılır, kurulum sonra yapılır.
 * Ortam elverişsizse modül hesapsız yoldan çalışmaya devam eder.
 */
class TelegramDoctorCommand extends Command
{
    protected $signature = 'telegram:doctor';

    protected $description = 'Hesaplı Telegram çekimi için sunucu ortamını kontrol eder.';

    /** MadelineProto'nun çalışması için gereken eklentiler. */
    private const REQUIRED_EXTENSIONS = ['mbstring', 'openssl', 'json', 'fileinfo', 'iconv', 'dom', 'zlib'];

    /** Olmasa da çalışır ama belirgin yavaşlama olur. */
    private const OPTIONAL_EXTENSIONS = ['gmp', 'ffi', 'sockets'];

    /** Telegram üretim veri merkezleri; birine bağlanabilmek yeterli. */
    private const TELEGRAM_ENDPOINTS = [
        'DC2 (Amsterdam)' => '149.154.167.51',
        'DC4 (Amsterdam)' => '149.154.167.91',
        'DC5 (Singapur)' => '91.108.56.130',
    ];

    /** MadelineProto tek oturumda bu kadarını rahatlıkla kullanabiliyor. */
    private const RECOMMENDED_MEMORY_BYTES = 256 * 1024 * 1024;

    private bool $blocked = false;

    public function handle(): int
    {
        $this->newLine();
        $this->info('Telegram hesaplı çekim — ortam kontrolü');
        $this->newLine();

        $rows = [
            $this->checkPhpVersion(),
            $this->checkRequiredExtensions(),
            $this->checkOptionalExtensions(),
            $this->checkMemoryLimit(),
            $this->checkLibrary(),
            $this->checkAppCredentials(),
            $this->checkConnectivity(),
            $this->checkSessionDirectory(),
            $this->checkAccounts(),
        ];

        $this->table(['Kontrol', 'Durum', 'Not'], $rows);
        $this->newLine();

        if ($this->blocked) {
            $this->error('Bu sunucuda hesaplı çekim çalışmaz. Yukarıdaki ✗ satırları giderilmeli.');
            $this->line('Giderilemezse modül hesapsız yoldan çalışmaya devam eder (görseller önizleme çözünürlüğünde).');

            return self::FAILURE;
        }

        $this->info('Ortam uygun.');

        if (! $this->libraryInstalled()) {
            $this->line('Sıradaki adım: composer require danog/madelineproto');
        }

        return self::SUCCESS;
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function checkPhpVersion(): array
    {
        $ok = version_compare(PHP_VERSION, '8.2.0', '>=');

        return $this->row('PHP sürümü', $ok, PHP_VERSION.($ok ? '' : ' — en az 8.2 gerekiyor'), blocking: true);
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function checkRequiredExtensions(): array
    {
        $missing = array_values(array_filter(
            self::REQUIRED_EXTENSIONS,
            static fn (string $extension): bool => ! extension_loaded($extension),
        ));

        return $this->row(
            'Zorunlu eklentiler',
            $missing === [],
            $missing === [] ? implode(', ', self::REQUIRED_EXTENSIONS) : 'eksik: '.implode(', ', $missing),
            blocking: true,
        );
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function checkOptionalExtensions(): array
    {
        $missing = array_values(array_filter(
            self::OPTIONAL_EXTENSIONS,
            static fn (string $extension): bool => ! extension_loaded($extension),
        ));

        // Engelleyici değil: yokluğunda yavaşlar, durmaz.
        return $this->row(
            'Önerilen eklentiler',
            $missing === [],
            $missing === [] ? implode(', ', self::OPTIONAL_EXTENSIONS) : 'eksik: '.implode(', ', $missing).' (yavaşlatır, engellemez)',
        );
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function checkMemoryLimit(): array
    {
        $raw = (string) ini_get('memory_limit');
        $bytes = $this->toBytes($raw);

        // -1 = sınırsız.
        $ok = $bytes < 0 || $bytes >= self::RECOMMENDED_MEMORY_BYTES;

        return $this->row(
            'Bellek sınırı',
            $ok,
            $raw.($ok ? '' : ' — 256M önerilir, CLI için php.ini ya da -d memory_limit ile artırılabilir'),
        );
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function checkLibrary(): array
    {
        $installed = $this->libraryInstalled();

        // Henüz kurulmamış olması bir hata değil: bu komut zaten kurulumdan
        // önce "kurmaya değer mi" sorusunu cevaplamak için var.
        return $this->row(
            'MadelineProto',
            $installed,
            $installed ? 'kurulu' : 'kurulu değil — ortam uygunsa: composer require danog/madelineproto',
        );
    }

    /**
     * Kurulum genelindeki uygulama kimliği.
     *
     * Tanımlıysa panele numarasını giren kişi yalnızca telefonunu yazıp
     * gelen kodu giriyor; my.telegram.org'a uğraması gerekmiyor.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkAppCredentials(): array
    {
        $id = config('storefront.telegram_client.api_id');
        $hash = config('storefront.telegram_client.api_hash');
        $configured = filled($id) && filled($hash);

        return $this->row(
            'Uygulama kimliği (.env)',
            $configured,
            $configured
                ? 'api_id '.$id.' — tüm numaralar bunu kullanır'
                : 'TELEGRAM_API_ID / TELEGRAM_API_HASH boş — her hesap kendi anahtarını girmek zorunda kalır',
        );
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function checkConnectivity(): array
    {
        $reachable = [];

        foreach (self::TELEGRAM_ENDPOINTS as $label => $ip) {
            if ($this->canConnect($ip, 443)) {
                $reachable[] = $label;
            }
        }

        return $this->row(
            'Telegram bağlantısı (TCP 443)',
            $reachable !== [],
            $reachable !== []
                ? 'açık: '.implode(', ', $reachable)
                : 'hiçbir veri merkezine ulaşılamadı — hosting giden bağlantıyı kapatmış olabilir',
            blocking: true,
        );
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function checkSessionDirectory(): array
    {
        // Oturum dosyası hesaba tam erişim demek: herkese açık diske değil,
        // private diske yazılıyor.
        $disk = Storage::disk('local');
        $probe = 'telegram/sessions/.doctor-probe';

        $ok = rescue(function () use ($disk, $probe): bool {
            $disk->put($probe, (string) now());
            $written = $disk->exists($probe);
            $disk->delete($probe);

            return $written;
        }, false, report: false);

        return $this->row(
            'Oturum klasörü yazılabilir',
            $ok,
            storage_path('app/private/telegram/sessions'),
            blocking: true,
        );
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function checkAccounts(): array
    {
        $total = TelegramAccount::query()->count();
        $ready = TelegramAccount::query()->ready()->count();

        if ($total === 0) {
            return $this->row('Tanımlı hesap', false, 'yok — panel → Telegram Hesapları\'ndan numara ekleyin');
        }

        return $this->row(
            'Tanımlı hesap',
            $ready > 0,
            $ready > 0
                ? $ready.' hesap girişi tamam ('.$total.' kayıt)'
                : $total.' kayıt var, girişi tamamlanmış hesap yok — php artisan telegram:login',
        );
    }

    private function libraryInstalled(): bool
    {
        return class_exists(API::class);
    }

    private function canConnect(string $ip, int $port): bool
    {
        $errorNumber = 0;
        $errorMessage = '';

        // Kapalı port genelde zaman aşımına düşüyor; kısa süre veriyoruz ki
        // komut üç veri merkezinde takılıp kalmasın.
        $socket = @fsockopen($ip, $port, $errorNumber, $errorMessage, 5);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }

    /** '256M' → 268435456, '-1' → -1 */
    private function toBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return -1;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function row(string $label, bool $ok, string $note, bool $blocking = false): array
    {
        if (! $ok && $blocking) {
            $this->blocked = true;
        }

        return [$label, $ok ? '<fg=green>✓</>' : ($blocking ? '<fg=red>✗</>' : '<fg=yellow>!</>'), $note];
    }
}
