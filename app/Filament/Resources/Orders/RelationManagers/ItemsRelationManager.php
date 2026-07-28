<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Sipariş kalemleri';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('product_name')->label('Ürün adı')->required(),
            TextInput::make('product_code')->label('Ürün kodu'),
            TextInput::make('size')->label('Beden'),
            TextInput::make('color')->label('Renk'),
            TextInput::make('quantity')->label('Adet')->numeric()->default(1)->required(),
            TextInput::make('unit_price')->label('Birim fiyat')->numeric()->minValue(0)->step('0.01'),
            TextInput::make('line_total')->label('Satır toplamı')->numeric()->minValue(0)->step('0.01'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_name')->label('Ürün'),
                TextColumn::make('product_code')->label('Kod')->placeholder('-'),
                TextColumn::make('size')->label('Beden')->placeholder('-'),
                TextColumn::make('color')->label('Renk')->placeholder('-'),
                TextColumn::make('quantity')->label('Adet'),
                TextColumn::make('unit_price')->label('Birim fiyat')->numeric(decimalPlaces: 2)->placeholder('-'),
                TextColumn::make('line_total')->label('Toplam')->numeric(decimalPlaces: 2)->placeholder('-'),
            ])
            ->headerActions([CreateAction::make()->label('Kalem ekle')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
