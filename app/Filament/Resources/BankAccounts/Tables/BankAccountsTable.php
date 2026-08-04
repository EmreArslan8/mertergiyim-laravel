<?php

namespace App\Filament\Resources\BankAccounts\Tables;

use App\Filament\Support\Reorderable;
use App\Support\BankCatalog;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BankAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(Reorderable::triggerAction())
            ->columns([
                ImageColumn::make('bank_logo')
                    ->label('Logo')
                    ->getStateUsing(fn ($record): ?string => BankCatalog::logoDataUri($record->bank_name))
                    // Yatay logolar sığmıyordu; sabit kutuya oranını bozmadan
                    // sığdır (contain) ve boyutu küçült.
                    ->imageWidth('7rem')
                    ->imageHeight('2.25rem')
                    ->extraImgAttributes(['style' => 'object-fit: contain; max-width: 100%;']),
                TextColumn::make('bank_name')->label('Banka')->searchable()->weight('bold'),
                TextColumn::make('account_type')->label('Hesap tipi')->placeholder('—'),
                TextColumn::make('account_holder')->label('Hesap sahibi')->searchable(),
                TextColumn::make('iban')->label('IBAN')->copyable()->copyMessage('IBAN kopyalandı'),
                TextColumn::make('branch')->label('Şube')->placeholder('—'),
                TextColumn::make('sort_order')->label('Sıra'),
                ToggleColumn::make('active')->label('Aktif / Pasif'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalDescription('Banka ve hesap bilgilerini güncelleyin.')
                    ->modalWidth(Width::SixExtraLarge)
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->extraModalWindowAttributes(['class' => 'merter-bank-account-modal']),
                DeleteAction::make(),
            ]);
    }
}
