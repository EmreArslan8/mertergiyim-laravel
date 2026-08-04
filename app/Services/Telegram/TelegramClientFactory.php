<?php

namespace App\Services\Telegram;

use App\Models\TelegramAccount;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use RuntimeException;

/**
 * Bir hesap için MadelineProto istemcisi kurar.
 *
 * Oturum klasörü hesap başına ayrı; aynı kurulumda birden çok numara
 * kullanılabilsin ve oturumlar birbirine karışmasın diye. Klasör herkese
 * açık diskte değil storage/app/private altında: oturum dosyası numaraya
 * tam erişim demek.
 */
class TelegramClientFactory
{
    public function make(TelegramAccount $account): API
    {
        if (! $account->hasCredentials()) {
            throw new RuntimeException(
                'Uygulama kimliği tanımlı değil. .env dosyasına TELEGRAM_API_ID ve TELEGRAM_API_HASH ekleyin '
                .'(my.telegram.org → API development tools) ya da bu hesap için panelden girin.'
            );
        }

        $sessionPath = storage_path('app/private/'.$account->sessionPath());

        // MadelineProto oturum klasörünü kendi oluşturuyor ama üst klasör
        // yoksa hata veriyor.
        $parent = dirname($sessionPath);

        if (! is_dir($parent) && ! @mkdir($parent, 0775, true) && ! is_dir($parent)) {
            throw new RuntimeException('Oturum klasörü oluşturulamadı: '.$parent);
        }

        $settings = $this->settings($account);

        // Paylaşımlı hostta (alwaysdata) MadelineProto'yu tek süreçte çalıştır.
        //
        // Varsayılan mimari, oturumu ayrı bir arka plan (IPC) sürecine devredip
        // ona bağlanıyor. alwaysdata gibi kalıcı arka plan süreci vermeyen
        // ortamlarda bu el sıkışması tamamlanmıyor ve MadelineProto 30 sn sonra
        // "Could not connect to MadelineProto" diye düşüyor. Altervista bayrağı
        // MadelineProto'nun tam da bu tür hostlar için yazılmış "forceFull"
        // yolunu açıyor: oturum fork'suz, mevcut süreçte yükleniyor. Ayarlar
        // kurulurken Magic::start çalıştığı için bayrağı API'den hemen önce,
        // yani en son yazıyoruz ki üzerine yazılmasın.
        Magic::$altervista = true;

        return new API($sessionPath, $settings);
    }

    private function settings(TelegramAccount $account): Settings
    {
        $appInfo = (new AppInfo)
            ->setApiId((int) $account->resolvedApiId())
            ->setApiHash((string) $account->resolvedApiHash());

        // Kütüphane varsayılanda her şeyi ekrana basıyor; komut çıktısı
        // okunmaz hale geliyordu. Uyarı ve üstü ayrı bir dosyaya yazılır.
        $logger = (new LoggerSettings)
            ->setType(Logger::LOGGER_FILE)
            ->setExtra(storage_path('logs/telegram-mtproto.log'))
            ->setLevel(Logger::LEVEL_WARNING);

        return (new Settings)
            ->setAppInfo($appInfo)
            ->setLogger($logger);
    }
}
