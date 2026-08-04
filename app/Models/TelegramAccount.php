<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Çekimde kullanılacak Telegram hesabı.
 *
 * Hesap yoksa modül hesapsız yoldan çalışmaya devam eder; hesap yalnızca
 * orijinal çözünürlüklü görsel ve video için gerekiyor.
 */
class TelegramAccount extends Model
{
    use HasUuidKey;

    public const STATUSES = [
        'new' => 'Giriş yapılmadı',
        'awaiting_code' => 'Kod bekleniyor',
        'active' => 'Bağlı',
        'failed' => 'Hatalı',
    ];

    protected $guarded = [];

    protected $casts = [
        // Uygulama parolası niteliğinde: veritabanında açık durmamalı.
        'api_hash' => 'encrypted',
        'active' => 'boolean',
        'sort_order' => 'integer',
        'last_used_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $account): void {
            $account->phone = self::normalizePhone($account->phone);
        });

        // Numara değişince kayıttaki her şey eski numaraya ait hale geliyor:
        // oturum da, api_id/api_hash de (bunlar my.telegram.org'a hangi
        // numarayla girildiyse ona bağlı). Kayıt "Bağlı" kalırsa tarama
        // sessizce eski numarayla çeker, anahtarlar kalırsa yeni numara
        // başkasının uygulama kimliğiyle bağlanır. İkisi de sıfırlanır.
        static::updating(function (self $account): void {
            if (! $account->isDirty('phone')) {
                return;
            }

            $account->forgetSession();

            $account->status = 'new';
            $account->session_path = null;
            $account->last_error = null;
            $account->last_used_at = null;

            // Kullanıcı aynı anda yeni anahtarları da girdiyse onlara dokunma.
            if (! $account->isDirty('api_id')) {
                $account->api_id = null;
            }

            if (! $account->isDirty('api_hash')) {
                $account->api_hash = null;
            }
        });

        // Oturum dosyası numaraya tam erişim demek; kayıt silinince diskte
        // kalmamalı.
        static::deleting(function (self $account): void {
            $account->forgetSession();
        });
    }

    /**
     * Diskteki MadelineProto oturumunu siler.
     *
     * Oturum bir klasör olarak tutuluyor; eski sürümlerden kalma tek dosyalık
     * hâline karşı ikisi de temizlenir.
     */
    public function forgetSession(): void
    {
        $path = $this->sessionPath();

        rescue(function () use ($path): void {
            $disk = Storage::disk('local');

            $disk->deleteDirectory($path);

            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }, report: false);
    }

    /**
     * Numarayı uluslararası biçime indirger: '0506 060 38 84' → '+905060603884'.
     *
     * Telegram numarayı bu biçimde bekliyor; kullanıcı ise başında sıfırla,
     * boşluklu, parantezli yazabiliyor. Benzersizlik kısıtının tutması için
     * tek biçime çevrilir.
     */
    public static function normalizePhone(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($digits === '') {
            return '';
        }

        // Baştaki sıfırlar: '0506...' ve uluslararası '00 90 506...' biçimleri.
        $digits = ltrim($digits, '0');

        // Ülke kodu yazılmamış TR cep numarası (5xx xxx xx xx).
        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            $digits = '90'.$digits;
        }

        return '+'.$digits;
    }

    /**
     * Kullanılacak uygulama kimliği.
     *
     * Anahtarlar kurulum genelinde (.env) tanımlı; kayıt bazındaki değer
     * yalnızca ayrı bir kimlikle çalışması gereken hesaplar için var ve
     * doluysa öne geçer.
     */
    public function resolvedApiId(): ?string
    {
        return filled($this->api_id)
            ? (string) $this->api_id
            : (config('storefront.telegram_client.api_id') ?: null);
    }

    public function resolvedApiHash(): ?string
    {
        return filled($this->api_hash)
            ? (string) $this->api_hash
            : (config('storefront.telegram_client.api_hash') ?: null);
    }

    /** Giriş denenebilir mi: numara ve bir yerden gelen anahtar çifti var mı? */
    public function hasCredentials(): bool
    {
        return filled($this->phone)
            && filled($this->resolvedApiId())
            && filled($this->resolvedApiHash());
    }

    public function scans(): HasMany
    {
        return $this->hasMany(TelegramScan::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** Yalnızca girişi tamamlanmış hesapla çekim yapılabilir. */
    public function scopeReady(Builder $query): Builder
    {
        return $query->where('active', true)->where('status', 'active');
    }

    public function isReady(): bool
    {
        return $this->active && $this->status === 'active';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function label(): string
    {
        return $this->label ?: $this->maskedPhone();
    }

    /** Panelde numaranın tamamı görünmesin: +90506•••••84 */
    public function maskedPhone(): string
    {
        $phone = (string) $this->phone;

        if (strlen($phone) <= 8) {
            return $phone;
        }

        return substr($phone, 0, 6).str_repeat('•', strlen($phone) - 8).substr($phone, -2);
    }

    /**
     * MadelineProto oturum klasörü.
     *
     * Kayıt başına ayrı klasör: aynı kurulumda birden çok numara
     * kullanılabilsin ve oturumlar birbirine karışmasın.
     */
    public function sessionPath(): string
    {
        // Yol bilerek kısa: MadelineProto'nun IPC Unix-socket'i bu klasörün
        // altına açılıyor ve Linux'ta socket yol sınırı 108 bayt. Derin proje
        // dizini + 36 karakterlik uuid ile toplam yol bu sınırı aşınca IPC
        // sunucusu başlayamıyor ("Could not connect to MadelineProto"). Kısa
        // hash ile klasör adı 12 karaktere iniyor, yol sınırın altında kalıyor.
        return $this->session_path ?: 'tg/'.substr(sha1($this->getKey()), 0, 12);
    }
}
