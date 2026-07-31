<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use App\Filament\Support\StorageUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class SiteSettingForm
{
    /**
     * site_settings.value jsonb yapısı: { locale: { alan: değer } }.
     */
    public const FIELDS = [
        'siteName' => 'Site adı',
        'footerBrand' => 'Alt bilgi marka adı',
        'footerDescription' => 'Alt bilgi açıklaması',
        'footerAddress' => 'Adres',
        'footerInfoTitle' => 'Bilgi başlığı',
        'copyright' => 'Telif metni',
        'contactTitle' => 'İletişim sayfası başlığı',
        'contactDescription' => 'İletişim sayfası açıklaması',
        'contactAddress' => 'Showroom adresi',
        'seoTitle' => 'Varsayılan SEO başlığı',
        'seoDescription' => 'Varsayılan SEO açıklaması',
        'seoKeywords' => 'SEO anahtar kelimeleri',
        'whatsappMessage' => 'Varsayılan WhatsApp mesajı',
        'maintenanceTitle' => 'Bakım sayfası başlığı',
        'maintenanceMessage' => 'Bakım sayfası açıklaması',
        'orderSuccessText' => 'Sipariş sonrası bilgilendirme',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Site ayarları')
                    ->tabs([
                        Tab::make('Genel')
                            ->schema([
                                Section::make('Genel bilgiler')
                                    ->description('Sitenin temel adresi, dili ve çalışma durumu.')
                                    ->columns(['default' => 1, 'md' => 2])
                                    ->schema([
                                        TextInput::make('value.general.siteUrl')
                                            ->label('Site URL’si')
                                            ->url()
                                            ->default(config('storefront.site_url'))
                                            ->placeholder('https://www.magaza.com')
                                            ->helperText('Canonical, sitemap ve robots.txt adreslerinde kullanılır.'),
                                        Select::make('value.general.defaultLocale')
                                            ->label('Varsayılan dil')
                                            ->options([
                                                'tr' => 'Türkçe',
                                                'en' => 'English',
                                                'ar' => 'العربية',
                                                'ru' => 'Русский',
                                                'fa' => 'فارسی',
                                                'uk' => 'Українська',
                                                'fr' => 'Français',
                                                'de' => 'Deutsch',
                                                'es' => 'Español',
                                                'it' => 'Italiano',
                                            ])
                                            ->default(config('storefront.default_locale')),
                                        Select::make('value.general.timezone')
                                            ->label('Saat dilimi')
                                            ->options([
                                                'Europe/Istanbul' => 'İstanbul (UTC+3)',
                                                'Europe/Berlin' => 'Berlin',
                                                'Europe/London' => 'Londra',
                                                'Asia/Dubai' => 'Dubai',
                                                'America/New_York' => 'New York',
                                            ])
                                            ->default(config('app.timezone'))
                                            ->searchable(),
                                        Toggle::make('value.general.maintenanceMode')
                                            ->label('Bakım modu')
                                            ->helperText('Açıldığında ziyaretçilere bakım mesajı gösterilir.')
                                            ->default(false),
                                        TextInput::make('value.general.whatsappNumber')
                                            ->label('WhatsApp numarası')
                                            ->tel()
                                            ->regex('/^[0-9]{10,15}$/')
                                            ->placeholder('905321234567')
                                            ->helperText('Ülke koduyla, sadece rakam kullanın.'),
                                        Textarea::make('value.tr.whatsappMessage')
                                            ->label(self::FIELDS['whatsappMessage'].' (Türkçe)')
                                            ->rows(2)
                                            ->placeholder('Merhaba, {product} ürünü hakkında bilgi almak istiyorum.')
                                            ->helperText('{product} alanı ürün kodu ve adıyla otomatik değiştirilir.')
                                            ->columnSpanFull(),
                                        TextInput::make('value.tr.maintenanceTitle')
                                            ->label(self::FIELDS['maintenanceTitle'].' (Türkçe)')
                                            ->placeholder('Kısa bir bakımdayız'),
                                        Textarea::make('value.tr.maintenanceMessage')
                                            ->label(self::FIELDS['maintenanceMessage'].' (Türkçe)')
                                            ->rows(2),
                                        TextInput::make('value.tr.footerInfoTitle')
                                            ->label(self::FIELDS['footerInfoTitle'].' (Türkçe)'),
                                        Textarea::make('value.tr.footerAddress')
                                            ->label(self::FIELDS['footerAddress'].' (Türkçe)')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Logo & Marka')
                            ->schema([
                                Section::make('Marka kimliği')
                                    ->description('Buradaki ad ve logo hem vitrinde hem yönetim panelinde kullanılır.')
                                    ->columns(['default' => 1, 'md' => 2])
                                    ->schema([
                                        TextInput::make('value.tr.siteName')
                                            ->label(self::FIELDS['siteName'].' (Türkçe)')
                                            ->required()
                                            ->helperText('Diğer 9 dil kaydederken otomatik hazırlanır.'),
                                        TextInput::make('value.tr.footerBrand')
                                            ->label(self::FIELDS['footerBrand'].' (Türkçe)'),
                                        StorageUpload::image('value.general.siteLogo', 'site', 'branding')
                                            ->label('Site logosu')
                                            ->imageEditor()
                                            ->helperText('Boş bırakılırsa site adı metin olarak gösterilir.'),
                                        StorageUpload::image('value.general.favicon', 'site', 'branding')
                                            ->label('Tarayıcı simgesi (favicon)')
                                            ->imageEditor()
                                            ->helperText('Kare bir görsel kullanın. Boşsa site logosu kullanılır.'),
                                        StorageUpload::image('value.general.socialShareImage', 'site', 'branding')
                                            ->label('Varsayılan paylaşım görseli')
                                            ->imageEditor()
                                            ->helperText('Önerilen ölçü: 1200 × 630 px.')
                                            ->columnSpanFull(),
                                        Textarea::make('value.tr.footerDescription')
                                            ->label(self::FIELDS['footerDescription'].' (Türkçe)')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        TextInput::make('value.tr.copyright')
                                            ->label(self::FIELDS['copyright'].' (Türkçe)')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Sipariş & Satış')
                            ->schema([
                                Section::make('Satış kuralları')
                                    ->columns(['default' => 1, 'md' => 2])
                                    ->schema([
                                        Select::make('value.general.salesMode')
                                            ->label('Satış türü')
                                            ->options([
                                                'wholesale' => 'Toptan',
                                                'retail' => 'Perakende',
                                                'both' => 'Toptan ve perakende',
                                            ])
                                            ->default('wholesale'),
                                        TextInput::make('value.general.orderNotificationChatId')
                                            ->label('Sipariş bildirimi Telegram Chat ID')
                                            ->placeholder('-1001234567890')
                                            ->helperText('Yeni sipariş bildiriminin gideceği Telegram kişi/grup chat id\'si. Grup ise "-100" ile başlar.'),
                                        TextInput::make('value.general.minimumOrderAmount')
                                            ->label('Minimum sipariş tutarı')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->helperText('0 girilirse alt limit uygulanmaz.'),
                                        Toggle::make('value.general.pricesIncludeTax')
                                            ->label('Fiyatlara KDV dahil')
                                            ->formatStateUsing(fn ($state): bool => $state === null ? true : (bool) $state)
                                            ->default(true),
                                        Toggle::make('value.general.allowOutOfStockOrders')
                                            ->label('Stok bitince siparişe izin ver')
                                            ->default(false),
                                        Textarea::make('value.tr.orderSuccessText')
                                            ->label(self::FIELDS['orderSuccessText'].' (Türkçe)')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('SEO')
                            ->schema([
                                Section::make('Arama motoru görünümü')
                                    ->description('Ürün veya içerik sayfasına özel değer yoksa bu bilgiler kullanılır.')
                                    ->schema([
                                        TextInput::make('value.tr.seoTitle')
                                            ->label(self::FIELDS['seoTitle'].' (Türkçe)')
                                            ->maxLength(60),
                                        Textarea::make('value.tr.seoDescription')
                                            ->label(self::FIELDS['seoDescription'].' (Türkçe)')
                                            ->rows(3)
                                            ->maxLength(160),
                                        TextInput::make('value.tr.seoKeywords')
                                            ->label(self::FIELDS['seoKeywords'].' (Türkçe)')
                                            ->helperText('Virgülle ayırın.'),
                                        StorageUpload::image('value.general.seoShareImage', 'site', 'seo')
                                            ->label('SEO paylaşım görseli')
                                            ->imageEditor()
                                            ->helperText('Boşsa Logo & Marka sekmesindeki paylaşım görseli kullanılır.'),
                                        TextInput::make('value.general.googleSiteVerification')
                                            ->label('Google doğrulama kodu')
                                            ->helperText('Yalnızca content değerini girin.'),
                                        Toggle::make('value.general.searchIndexingEnabled')
                                            ->label('Arama motorlarında indekslemeye izin ver')
                                            ->formatStateUsing(fn ($state): bool => $state === null ? true : (bool) $state)
                                            ->default(true),
                                    ]),
                            ]),

                        Tab::make('İletişim')
                            ->schema([
                                Section::make('İletişim sayfası')
                                    ->columns(['default' => 1, 'md' => 2])
                                    ->schema([
                                        TextInput::make('value.general.contactPhone')
                                            ->label('İletişim telefonu')
                                            ->tel()
                                            ->placeholder('+90 532 123 45 67'),
                                        TextInput::make('value.general.contactEmail')
                                            ->label('E-posta')
                                            ->email()
                                            ->placeholder('info@magaza.com'),
                                        TextInput::make('value.tr.contactTitle')
                                            ->label(self::FIELDS['contactTitle'].' (Türkçe)'),
                                        Textarea::make('value.tr.contactDescription')
                                            ->label(self::FIELDS['contactDescription'].' (Türkçe)')
                                            ->rows(3),
                                        Textarea::make('value.tr.contactAddress')
                                            ->label(self::FIELDS['contactAddress'].' (Türkçe)')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        Textarea::make('value.general.googleMapsIframe')
                                            ->label('Google Maps iframe kodu')
                                            ->rows(4)
                                            ->dehydrateStateUsing(fn ($state): string => (string) $state)
                                            ->helperText('Google Maps > Paylaş > Harita yerleştir alanındaki iframe kodunu yapıştırın.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Sosyal')
                            ->schema([
                                Section::make('Sosyal medya')
                                    ->columns(['default' => 1, 'md' => 2])
                                    ->schema([
                                        TextInput::make('value.general.instagramUrl')
                                            ->label('Instagram bağlantısı')
                                            ->url()
                                            ->placeholder('https://instagram.com/magaza'),
                                        TextInput::make('value.general.facebookUrl')
                                            ->label('Facebook bağlantısı')
                                            ->url()
                                            ->placeholder('https://facebook.com/magaza'),
                                        TextInput::make('value.general.tiktokUrl')
                                            ->label('TikTok bağlantısı')
                                            ->url()
                                            ->placeholder('https://tiktok.com/@magaza'),
                                        TextInput::make('value.general.youtubeUrl')
                                            ->label('YouTube bağlantısı')
                                            ->url()
                                            ->placeholder('https://youtube.com/@magaza'),
                                        TextInput::make('value.general.linkedinUrl')
                                            ->label('LinkedIn bağlantısı')
                                            ->url()
                                            ->placeholder('https://linkedin.com/company/magaza'),
                                    ]),
                            ]),

                        Tab::make('Analitik & Script')
                            ->schema([
                                Section::make('Ölçüm kodları')
                                    ->description('Sadece kimliği girin; gerekli güvenli script siteye otomatik eklenir.')
                                    ->columns(['default' => 1, 'md' => 2])
                                    ->schema([
                                        TextInput::make('value.general.googleAnalyticsId')
                                            ->label('Google Analytics ölçüm kimliği')
                                            ->placeholder('G-XXXXXXXXXX')
                                            ->regex('/^G-[A-Z0-9]+$/i'),
                                        TextInput::make('value.general.googleTagManagerId')
                                            ->label('Google Tag Manager kimliği')
                                            ->placeholder('GTM-XXXXXXX')
                                            ->regex('/^GTM-[A-Z0-9]+$/i'),
                                        TextInput::make('value.general.metaPixelId')
                                            ->label('Meta Pixel kimliği')
                                            ->placeholder('123456789012345')
                                            ->regex('/^[0-9]+$/'),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString('ayar-sekmesi')
                    ->columnSpanFull(),
            ]);
    }
}
