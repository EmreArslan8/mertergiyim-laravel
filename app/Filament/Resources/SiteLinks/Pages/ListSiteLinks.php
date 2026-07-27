<?php

namespace App\Filament\Resources\SiteLinks\Pages;

use App\Filament\Resources\SiteLinks\SiteLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiteLinks extends ListRecords
{
    protected static string $resource = SiteLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
