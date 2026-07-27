<?php

namespace App\Filament\Resources\SiteLinks\Pages;

use App\Filament\Resources\SiteLinks\SiteLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSiteLink extends EditRecord
{
    protected static string $resource = SiteLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
