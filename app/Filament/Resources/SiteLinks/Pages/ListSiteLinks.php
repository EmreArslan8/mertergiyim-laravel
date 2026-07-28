<?php

namespace App\Filament\Resources\SiteLinks\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\SiteLinks\SiteLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiteLinks extends ListRecords
{
    use TranslatesJsonFields;

    protected static string $resource = SiteLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(fn (array $data): array => $this->fillAutomaticTranslationsFor($data, null)),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return ['label' => 'Etiket'];
    }
}
