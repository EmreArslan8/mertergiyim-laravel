<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Currency;
use App\Support\OrderStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class OrderForm
{
    /**
     * Kaynak paneldeki kargo firması listesi ve takip adresleri.
     */
    public const CARGO_COMPANIES = [
        'Yurtiçi Kargo' => 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula',
        'Aras Kargo' => 'https://www.araskargo.com.tr/trmobile/cargo_tracking_page',
        'MNG Kargo' => 'https://www.mngkargo.com.tr/gonderitakip',
        'Sürat Kargo' => 'https://suratkargo.com.tr/KargoTakip',
        'PTT Kargo' => 'https://gonderitakip.ptt.gov.tr/',
        'UPS Türkiye' => 'https://www.ups.com/track?loc=tr_TR',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sipariş')
                    ->columns(2)
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Sipariş no')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'MG-'.now()->format('ymd').'-'.strtoupper(Str::random(4))),
                        TextInput::make('tracking_code')
                            ->label('Takip kodu')
                            ->helperText('Müşteri sipariş takibi sayfasında bu kodu kullanır.')
                            ->default(fn () => strtoupper(Str::random(8))),
                        Select::make('status')
                            ->label('Durum')
                            ->options(OrderStatus::labels())
                            ->default('new')
                            ->required(),
                        TextInput::make('total')
                            ->label('Toplam')
                            ->numeric()
                            ->step('0.01')
                            ->suffix(fn () => Currency::query()->where('is_default', true)->value('symbol') ?? 'TL'),
                    ]),

                Section::make('Müşteri')
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer_name')->label('Ad soyad')->required(),
                        TextInput::make('phone')->label('Telefon')->tel()->required(),
                        Textarea::make('address')->label('Adres')->rows(3)->columnSpanFull(),
                        Textarea::make('note')->label('Not')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Kargo')
                    ->columns(2)
                    ->schema([
                        Select::make('cargo_company')
                            ->label('Kargo firması')
                            ->options(array_combine(array_keys(self::CARGO_COMPANIES), array_keys(self::CARGO_COMPANIES)))
                            ->searchable()
                            ->live()
                            // Firma seçilince takip adresi otomatik dolsun.
                            ->afterStateUpdated(fn ($state, $set) => $set('cargo_tracking_url', self::CARGO_COMPANIES[$state] ?? null)),
                        TextInput::make('cargo_tracking_url')->label('Kargo takip bağlantısı')->url(),
                    ]),
            ]);
    }
}
