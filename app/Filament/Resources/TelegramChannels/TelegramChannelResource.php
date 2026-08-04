<?php

namespace App\Filament\Resources\TelegramChannels;

use App\Filament\Resources\ManagedResource;
use App\Filament\Resources\TelegramChannels\Pages\ListTelegramChannels;
use App\Filament\Resources\TelegramChannels\Schemas\TelegramChannelForm;
use App\Filament\Resources\TelegramChannels\Tables\TelegramChannelsTable;
use App\Models\TelegramChannel;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Ürün çekilecek kanallar. Menüde görünmez; Telegram Ürünleri ekranından
 * açılır, modülün diğer ekranlarıyla aynı akışta.
 */
class TelegramChannelResource extends ManagedResource
{
    protected static ?string $model = TelegramChannel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'kanal';

    protected static ?string $pluralModelLabel = 'kanallar';

    public static function form(Schema $schema): Schema
    {
        return TelegramChannelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TelegramChannelsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTelegramChannels::route('/'),
        ];
    }
}
