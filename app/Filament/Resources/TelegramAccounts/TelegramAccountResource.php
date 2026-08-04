<?php

namespace App\Filament\Resources\TelegramAccounts;

use App\Filament\Resources\ManagedResource;
use App\Filament\Resources\TelegramAccounts\Pages\ListTelegramAccounts;
use App\Filament\Resources\TelegramAccounts\Schemas\TelegramAccountForm;
use App\Filament\Resources\TelegramAccounts\Tables\TelegramAccountsTable;
use App\Models\TelegramAccount;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Çekimde kullanılacak numaralar. ManagedResource'un editör listesinde
 * olmadığı için yalnızca süper yöneticiye açık.
 */
class TelegramAccountResource extends ManagedResource
{
    protected static ?string $model = TelegramAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    /** Menüde görünmez; Telegram > Ürünler ekranından açılıyor. */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'telegram hesabı';

    protected static ?string $pluralModelLabel = 'telegram hesapları';

    public static function form(Schema $schema): Schema
    {
        return TelegramAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TelegramAccountsTable::configure($table);
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
            'index' => ListTelegramAccounts::route('/'),
        ];
    }
}
