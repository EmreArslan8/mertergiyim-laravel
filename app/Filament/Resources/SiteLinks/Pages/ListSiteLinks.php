<?php

namespace App\Filament\Resources\SiteLinks\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\SiteLinks\SiteLinkResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListSiteLinks extends ListRecords
{
    use TranslatesJsonFields;

    protected static string $resource = SiteLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->fillForm(fn (): array => [
                    'location' => in_array($this->activeTab, ['header', 'footer'], true)
                        ? $this->activeTab
                        : 'header',
                ])
                ->mutateDataUsing(fn (array $data): array => $this->fillAutomaticTranslationsFor($data, null)),
        ];
    }

    public function getTabs(): array
    {
        return [
            'header' => Tab::make('Üst Menü')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('location', 'header')),
            'footer' => Tab::make('Alt Menü')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('location', 'footer')),
        ];
    }

    public function getTabsContentComponent(): Component
    {
        $tabs = $this->getCachedTabs();

        $menuTabs = Tabs::make()
            ->key('resourceTabs')
            ->livewireProperty('activeTab')
            ->contained(false)
            ->tabs($tabs)
            ->extraAttributes(['class' => 'merter-menu-tabs']);

        return Flex::make([
            $menuTabs,
            Action::make('reorderSiteLinks')
                ->label(fn (): string => $this->isTableReordering()
                    ? 'Sıralamayı bitir'
                    : 'Manuel sırayı düzenle')
                ->icon(Heroicon::ArrowsUpDown)
                ->button()
                ->color('gray')
                ->extraAttributes(['class' => 'merter-menu-reorder-button'])
                ->action('toggleTableReordering'),
        ])
            ->extraAttributes(['class' => 'merter-menu-tabs-row'])
            ->hidden(empty($tabs));
    }

    protected function translatableJsonFields(): array
    {
        return ['label' => 'Etiket'];
    }
}
