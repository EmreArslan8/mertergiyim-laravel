<?php

namespace App\Filament\Resources\SiteLinks;

use App\Filament\Resources\SiteLinks\Pages\CreateSiteLink;
use App\Filament\Resources\SiteLinks\Pages\EditSiteLink;
use App\Filament\Resources\SiteLinks\Pages\ListSiteLinks;
use App\Filament\Resources\SiteLinks\Schemas\SiteLinkForm;
use App\Filament\Resources\SiteLinks\Tables\SiteLinksTable;
use App\Models\SiteLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SiteLinkResource extends Resource
{
    protected static ?string $model = SiteLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?string $navigationLabel = 'Menü bağlantıları';

    protected static ?string $modelLabel = 'bağlantı';

    protected static ?string $pluralModelLabel = 'bağlantılar';

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SiteLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiteLinksTable::configure($table);
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
            'index' => ListSiteLinks::route('/'),
            'create' => CreateSiteLink::route('/create'),
            'edit' => EditSiteLink::route('/{record}/edit'),
        ];
    }
}
