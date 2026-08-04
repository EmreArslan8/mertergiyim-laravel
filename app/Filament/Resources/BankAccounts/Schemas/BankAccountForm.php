<?php

namespace App\Filament\Resources\BankAccounts\Schemas;

use App\Support\BankCatalog;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // Modal zaten kart görünümü sağlıyor; iç Section fazladan çerçeve/başlık
            // getiriyordu. Çerçevesiz Grid ile sadece alan düzenini koruyoruz.
            // Alanlar: Banka, Hesap sahibi, Hesap tipi, Şube, IBAN.
            // sort_order / active (default'lu) formda yok; DB varsayılanlarıyla
            // kaydediliyor. Şube kolonu DB'de nullable ama formda zorunlu.
            Grid::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Radio::make('bank_name')
                        ->label('Banka')
                        ->options(BankCatalog::options())
                        ->required()
                        // Bir hesap tek bankaya ait; 20 banka dropdown yerine
                        // tıklanabilir liste olarak, yer kaplamasın diye çok kolonlu.
                        ->columns(4)
                        ->helperText('Banka logosu seçiminize göre otomatik gösterilir.')
                        ->columnSpanFull(),
                    TextInput::make('account_holder')
                        ->label('Hesap sahibi')
                        ->required()
                        ->maxLength(150)
                        ->columnSpan(1),
                    TextInput::make('account_type')
                        ->label('Hesap tipi')
                        ->placeholder('TL Vadesiz')
                        ->maxLength(100)
                        ->columnSpan(1),
                    TextInput::make('branch')
                        ->label('Şube')
                        ->required()
                        ->maxLength(150)
                        ->placeholder('Merter Şubesi')
                        ->columnSpan(1),
                    TextInput::make('iban')
                        ->label('IBAN')
                        ->required()
                        ->maxLength(34)
                        ->helperText('TR ile başlayan IBAN bilgisini boşluksuz girin.')
                        ->columnSpan(1),
                ]),
        ]);
    }
}
