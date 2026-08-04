<?php

namespace App\Filament\Resources\Homepage;

use App\Filament\Resources\Homepage\Pages\EditHomepage;
use App\Filament\Resources\Homepage\Schemas\HomepageForm;
use App\Filament\Resources\ManagedResource;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class HomepageResource extends ManagedResource
{
    protected static ?string $model = SiteSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Ana Sayfa Yönetimi';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'homepage';

    protected static ?string $modelLabel = 'ana sayfa';

    protected static ?string $pluralModelLabel = 'ana sayfa';

    public static function form(Schema $schema): Schema
    {
        return HomepageForm::configure($schema);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationUrl(): string
    {
        return static::getIndexUrl();
    }

    /**
     * Filament, index sayfası olmayan kaynakları varsayılan olarak sidebar'da
     * göstermiyor. Bu singleton ekran doğrudan storefront kaydını düzenler.
     *
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->key(static::class)
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.homepage.*'))
                ->sort(static::getNavigationSort())
                ->url(static::getNavigationUrl()),
        ];
    }

    public static function getIndexUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
    ): string {
        return static::getUrl(
            'edit',
            [...$parameters, 'record' => 'storefront'],
            $isAbsolute,
            $panel,
            $tenant,
            $shouldGuessMissingParameters,
        );
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditHomepage::route('/{record}/edit'),
        ];
    }
}
