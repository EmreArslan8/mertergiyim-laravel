<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Services\AdminOptionService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Beden / renk';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            // Beden/renk listeleri tek haneli: searchable() alanı JS bileşenine
            // çevirip seçenekleri istekle çektiği için "Yükleniyor…" görünüyordu.
            // Native select ile seçenekler sayfayla birlikte gelir.
            Select::make('size_id')
                ->label('Beden')
                ->options(fn () => app(AdminOptionService::class)->sizes()),
            Select::make('color_id')
                ->label('Renk')
                ->options(fn () => app(AdminOptionService::class)->colors()),
            TextInput::make('stock_quantity')->label('Stok adedi')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->columns([
                TextColumn::make('size.name')->label('Beden')->placeholder('-'),
                ColorColumn::make('color.hex')->label('Renk'),
                TextColumn::make('color.name')->label('Renk adı')->placeholder('-'),
                TextColumn::make('stock_quantity')->label('Stok'),
            ])
            ->headerActions([CreateAction::make()->label('Varyant ekle')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
