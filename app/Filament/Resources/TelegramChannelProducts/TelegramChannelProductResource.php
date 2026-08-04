<?php

namespace App\Filament\Resources\TelegramChannelProducts;

use App\Filament\Resources\ManagedResource;
use App\Filament\Resources\TelegramChannelProducts\Pages\ListTelegramChannelProducts;
use App\Filament\Resources\TelegramChannelProducts\Tables\TelegramChannelProductsTable;
use App\Models\TelegramChannelProduct;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TelegramChannelProductResource extends ManagedResource
{
    protected static ?string $model = TelegramChannelProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $navigationLabel = 'Telegram Ürünleri';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'telegram ürünü';

    protected static ?string $pluralModelLabel = 'telegram ürünleri';

    public static function table(Table $table): Table
    {
        return TelegramChannelProductsTable::configure($table);
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
            'index' => ListTelegramChannelProducts::route('/'),
        ];
    }
}
