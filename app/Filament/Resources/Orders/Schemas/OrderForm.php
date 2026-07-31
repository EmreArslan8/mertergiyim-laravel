<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Currency;
use App\Support\OrderStatus;
use App\Support\PhoneNumber;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Yeni sipariş formu (panelden elle açılan siparişler).
 *
 * Düzen detay sayfasıyla aynı mantıkta: solda siparişin künyesi ve kalemleri,
 * sağda müşteri ve kargo bağlamı. Toplam alanı yok — kalemlerden türetilir
 * (bkz. CreateOrder::afterCreate).
 */
class OrderForm
{
    /**
     * Türkiye'de gönderi taşıyan kargo firmaları ve takip sayfaları.
     *
     * Değer, firmanın gönderi takip adresi; bilinmeyenler null bırakılır ve
     * takip bağlantısı elle girilir. Liste sipariş detayındaki seçeneklerin
     * çekirdeği: kullanıcı "+" ile buraya olmayan bir firma da ekleyebilir,
     * eklenen ad siparişlerden okunup listeye kendiliğinden katılır.
     */
    public const CARGO_COMPANIES = [
        'Aras Kargo' => 'https://www.araskargo.com.tr/trmobile/cargo_tracking_page',
        'DHL Express' => 'https://www.dhl.com/tr-tr/home/tracking.html',
        'FedEx' => 'https://www.fedex.com/tr-tr/tracking.html',
        'HepsiJET' => null,
        'Horoz Lojistik' => null,
        'Kolay Gelsin' => null,
        'MNG Kargo' => 'https://www.mngkargo.com.tr/gonderitakip',
        'PTT Kargo' => 'https://gonderitakip.ptt.gov.tr/',
        'Sendeo' => null,
        'Sürat Kargo' => 'https://suratkargo.com.tr/KargoTakip',
        'TNT Express' => 'https://www.tnt.com/express/tr_tr/site/shipping-tools/tracking.html',
        'Trendyol Express' => null,
        'UPS Türkiye' => 'https://www.ups.com/track?loc=tr_TR',
        'Yurtiçi Kargo' => 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            // Sağ kolon ⅓ iken müşteri alanları sıkışıyordu: 3/2 (%60–40).
            ->columns(['default' => 1, 'xl' => 5])
            ->components([
                Group::make()
                    ->columnSpan(['default' => 1, 'xl' => 3])
                    ->schema([
                        // Siparişin künyesi önce: numara, takip kodu, durum,
                        // para birimi. Kalemler bunun altında, çünkü ürün
                        // fiyatları seçilen para birimine göre okunuyor.
                        Section::make('Sipariş')
                            ->columns(['default' => 1, 'md' => 2])
                            ->schema([
                                TextInput::make('order_number')
                                    ->label('Sipariş no')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn () => 'MG-'.now()->format('ymd').'-'.strtoupper(Str::random(4))),
                                TextInput::make('tracking_code')
                                    ->label('Müşteri takip kodu')
                                    ->helperText('Müşteri sipariş takibi sayfasında bu kodu kullanır.')
                                    ->default(fn () => strtoupper(Str::random(8))),
                                Select::make('status')
                                    ->label('Durum')
                                    ->options(OrderStatus::labels())
                                    ->default('new')
                                    ->selectablePlaceholder(false)
                                    // Kargo alanının kilidi duruma bağlı.
                                    ->live()
                                    ->required(),
                                Select::make('currency')
                                    ->label('Para birimi')
                                    ->options(fn () => Currency::query()->pluck('code', 'code'))
                                    ->default('TRY')
                                    ->selectablePlaceholder(false)
                                    ->required(),
                            ]),

                        // Siparişin kendisi kalemlerdir. Ürün katalogdan
                        // seçilir, beden/renk o ürünün varyantlarından gelir,
                        // satır toplamı hesaplanır.
                        Section::make('Ürünler')
                            ->schema([
                                Repeater::make('items')
                                    ->relationship()
                                    ->hiddenLabel()
                                    ->addActionLabel('Kalem ekle')
                                    ->defaultItems(1)
                                    // Kalemsiz sipariş kaydedilemez: sipariş kalemlerdir.
                                    ->minItems(1)
                                    ->itemLabel(fn (array $state): ?string => $state['product_name'] ?? null)
                                    ->columns(['default' => 1, 'md' => 12])
                                    ->mutateRelationshipDataBeforeCreateUsing(
                                        fn (array $data): array => OrderItems::attributes($data),
                                    )
                                    ->schema(OrderItems::fields()),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(['default' => 1, 'xl' => 2])
                    ->schema([
                        Section::make('Müşteri')
                            ->schema([
                                // Vitrindeki checkout ile aynı asgari bilgi seti:
                                // ad soyad, geçerli telefon ve teslimat adresi.
                                // Panelden de eksik sipariş açılamamalı.
                                TextInput::make('customer_name')
                                    ->label('Ad soyad')
                                    ->required()
                                    ->maxLength(120),
                                TextInput::make('phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->required()
                                    ->maxLength(30)
                                    // Kural closure'ı sarmalanmadan verilirse Filament
                                    // onu kendi parametre enjeksiyonuyla çağırmaya çalışır.
                                    ->rule(fn () => PhoneNumber::rule('Geçerli bir telefon numarası girin.'))
                                    ->helperText('Ülke koduyla veya 0 ile başlayarak girebilirsiniz.'),
                                Textarea::make('address')
                                    ->label('Teslimat adresi')
                                    ->rows(3)
                                    ->required()
                                    ->maxLength(2000),
                                Textarea::make('note')->label('Müşteri notu')->rows(2)->maxLength(2000),
                            ]),

                        Section::make('Kargo')
                            ->schema([OrderItems::cargoCompanySelect()]),
                    ]),
            ]);
    }
}
