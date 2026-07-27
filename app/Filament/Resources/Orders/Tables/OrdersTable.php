<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Support\OrderStatus;
use App\Support\Storefront;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')->label('Sipariş no')->weight('bold')->searchable(),
                TextColumn::make('customer_name')
                    ->label('Müşteri')
                    ->description(fn ($record) => $record->phone)
                    ->searchable(),
                TextColumn::make('tracking_code')->label('Takip kodu')->placeholder('-')->searchable(),
                TextColumn::make('total')
                    ->label('Toplam')
                    ->formatStateUsing(fn ($state) => Storefront::formatPrice($state)),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => OrderStatus::label($state))
                    ->color(fn ($state) => OrderStatus::color($state)),
                TextColumn::make('cargo_company')->label('Kargo')->placeholder('-')->toggleable(),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Durum')->options(OrderStatus::labels()),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
