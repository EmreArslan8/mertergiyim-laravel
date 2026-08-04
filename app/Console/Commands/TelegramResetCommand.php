<?php

namespace App\Console\Commands;

use App\Models\TelegramAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Kurulumu başkasına devretmeden önce Telegram kimliklerini temizler.
 *
 * Oturum dosyası numaraya TAM ERİŞİM demek: kopyalayan kişi o hesaba mesaj
 * atabilir, sohbetleri okuyabilir. Klasörü zip'leyip ya da sunucu imajını
 * devrederken bu dosyaların gitmesi en ciddi sızma yolu. Bu komut hesap
 * kayıtlarını ve oturumları siler; çekilmiş ürünlere dokunmaz.
 *
 * Anahtarlar .env'de olduğu için onları komut silmez — .env zaten devredilen
 * pakete girmemeli, çıktıda hatırlatılıyor.
 */
class TelegramResetCommand extends Command
{
    protected $signature = 'telegram:reset {--force : Onay sorma}';

    protected $description = 'Telegram hesaplarını ve oturum dosyalarını siler (kurulum devri öncesi).';

    public function handle(): int
    {
        $accounts = TelegramAccount::query()->get();

        $this->newLine();

        if ($accounts->isEmpty()) {
            $this->line('Kayıtlı hesap yok.');
        } else {
            $this->line('Silinecek hesaplar:');

            foreach ($accounts as $account) {
                $this->line('  · '.$account->label().' ('.$account->phone.') — '.$account->statusLabel());
            }
        }

        $strays = $this->straySessions($accounts);

        if ($strays !== []) {
            $this->newLine();
            $this->line('Sahipsiz oturum klasörleri:');

            foreach ($strays as $stray) {
                $this->line('  · '.$stray);
            }
        }

        if ($accounts->isEmpty() && $strays === []) {
            $this->info('Temizlenecek bir şey yok.');

            $this->reminders();

            return self::SUCCESS;
        }

        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Silinsin mi? Geri alınamaz, hesaplara yeniden giriş gerekir.')) {
            $this->line('Vazgeçildi.');

            return self::SUCCESS;
        }

        // Model kaydı silinince oturum klasörü de gidiyor (deleting kancası).
        foreach ($accounts as $account) {
            $account->delete();
        }

        foreach ($strays as $stray) {
            Storage::disk('local')->deleteDirectory($stray);
        }

        $this->info('Hesaplar ve oturumlar silindi. Çekilmiş ürünler ve tarama geçmişi duruyor.');

        $this->reminders();

        return self::SUCCESS;
    }

    /**
     * Kayıtla eşleşmeyen oturum klasörleri.
     *
     * Numara değiştirilip kayıt silinmişse ya da eski bir kurulumdan kalmışsa
     * diskte sahipsiz oturum durabiliyor; devirde en tehlikelisi bunlar,
     * çünkü panelde görünmüyorlar.
     *
     * @param  Collection<int, TelegramAccount>  $accounts
     * @return array<int, string>
     */
    private function straySessions($accounts): array
    {
        $known = $accounts
            ->map(fn (TelegramAccount $account): string => $account->sessionPath())
            ->all();

        return array_values(array_filter(
            Storage::disk('local')->directories('telegram/sessions'),
            static fn (string $directory): bool => ! in_array($directory, $known, true),
        ));
    }

    private function reminders(): void
    {
        $this->newLine();
        $this->line('<options=bold>Devirden önce elle yapılacaklar:</>');
        $this->line('  1. .env dosyasını pakete koymayın — TELEGRAM_API_ID / TELEGRAM_API_HASH sizin uygulama kimliğiniz.');
        $this->line('  2. Veritabanı dökümü veriyorsanız telegram_accounts tablosunu boşaltın.');
        $this->line('  3. APP_KEY\'i yenileyin (php artisan key:generate) — eski anahtarla şifreli alanlar çözülebilir.');
        $this->line('  4. Karşı taraf my.telegram.org\'dan KENDİ uygulamasını oluşturup kendi .env\'ine yazsın.');
        $this->newLine();
    }
}
