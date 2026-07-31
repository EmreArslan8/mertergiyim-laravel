<?php

namespace App\Filament\Resources\Colors;

use App\Filament\Resources\ManagedResource;
use App\Filament\Resources\Colors\Pages\ListColors;
use App\Filament\Resources\Colors\Schemas\ColorForm;
use App\Filament\Resources\Colors\Tables\ColorsTable;
use App\Models\Color;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ColorResource extends ManagedResource
{
    protected static ?string $model = Color::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $navigationLabel = 'Renkler';

    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'renk';

    protected static ?string $pluralModelLabel = 'renkler';

    public static function form(Schema $schema): Schema
    {
        return ColorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ColorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListColors::route('/'),
        ];
    }
}
