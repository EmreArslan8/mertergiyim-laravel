<?php

namespace App\Filament\Resources\TelegramAccounts\Schemas;

use App\Models\TelegramAccount;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class TelegramAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            // Tek kolon, kart (Section kutusu) yok: alanlar doğrudan modalda.
            ->columns(1)
            ->components([
                Text::make('Orijinal çözünürlüklü görsel ve video yalnızca hesapla çekilebiliyor. Hesap tanımlanmazsa modül hesapsız yoldan çalışmaya devam eder.'),

                // Hesap adı ve numara aynı satırda yan yana.
                Grid::make(2)->schema([
                    TextInput::make('label')
                        ->label('Hesap adı')
                        ->placeholder('Merter ana hat')
                        ->maxLength(120)
                        ->helperText('Panelde ayırt etmek için. Boş bırakılırsa numara gösterilir.'),

                    TextInput::make('phone')
                        ->label('Telefon numarası')
                        ->tel()
                        ->required()
                        ->maxLength(32)
                        ->placeholder('+90 506 060 38 84')
                        // Kayıt sırasında modelde de normalleştiriliyor;
                        // buradaki dönüşüm kullanıcıya doğrulama hatasından
                        // önce doğru biçimi göstermek için.
                        ->dehydrateStateUsing(fn (?string $state): string => TelegramAccount::normalizePhone($state))
                        ->unique(table: TelegramAccount::class, column: 'phone', ignoreRecord: true)
                        ->validationMessages(['unique' => 'Bu numara zaten kayıtlı.'])
                        ->helperText(fn (string $operation): string => $operation === 'edit'
                            ? 'Numarayı değiştirirseniz oturum ve anahtarlar sıfırlanır; yeni numaranın api_id/api_hash değerlerini girip tekrar giriş yapmanız gerekir. Eski numarayı da kullanmaya devam edecekseniz değiştirmek yerine ayrı bir hesap ekleyin.'
                            : 'Kod bu numaraya gelecek. Başında sıfırla da yazabilirsiniz.'),
                ]),

                // Anahtarlar normalde .env'den geliyor: api_id
                // uygulamayı temsil ediyor, kullanıcıyı değil. Buraya
                // yalnızca ayrı bir kimlikle çalışması gereken hesap
                // için girilir.
                TextInput::make('api_id')
                    ->label('api_id (isteğe bağlı)')
                    ->numeric()
                    ->maxLength(32)
                    ->placeholder(self::appCredentialsConfigured() ? 'Kurulumun anahtarı kullanılacak' : 'my.telegram.org → API development tools')
                    ->helperText(self::credentialsHelp()),

                TextInput::make('api_hash')
                    ->label('api_hash (isteğe bağlı)')
                    ->password()
                    ->revealable()
                    ->maxLength(64)
                    ->placeholder(self::appCredentialsConfigured() ? 'Kurulumun anahtarı kullanılacak' : 'my.telegram.org → API development tools')
                    ->helperText('Şifrelenerek saklanır, listede gösterilmez.'),

                Toggle::make('active')
                    ->label('Durum')
                    ->default(true)
                    ->live()
                    ->onColor('success')
                    ->offColor('danger')
                    ->helperText(fn (?bool $state): string => $state ? 'Aktif' : 'Pasif'),

                // Giriş yalnızca kayıt sonrası; yeni kayıtta gizli.
                Text::make('Giriş: Kaydı oluşturduktan sonra sunucuda şu komutu bir kez çalıştırın: php artisan telegram:login — numaraya gelen SMS kodunu ve varsa iki adımlı doğrulama parolanızı ister. Oturum bir kez kaydedilir, sonraki taramalarda tekrar kod istenmez.')
                    ->visibleOn('edit'),
            ]);
    }

    private static function appCredentialsConfigured(): bool
    {
        return filled(config('storefront.telegram_client.api_id'))
            && filled(config('storefront.telegram_client.api_hash'));
    }

    private static function credentialsHelp(): string
    {
        return self::appCredentialsConfigured()
            ? 'Boş bırakın. Kurulumun uygulama kimliği kullanılır; api_id kullanıcıyı değil uygulamayı temsil ettiği için her numara için ayrı anahtar gerekmez.'
            : 'Kurulumda TELEGRAM_API_ID / TELEGRAM_API_HASH tanımlı değil. Ya .env dosyasına ekleyin ya da bu hesap için buraya girin (my.telegram.org → API development tools).';
    }
}
