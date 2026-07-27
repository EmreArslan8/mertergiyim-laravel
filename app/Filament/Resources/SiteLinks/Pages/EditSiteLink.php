<?php

namespace App\Filament\Resources\SiteLinks\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\SiteLinks\SiteLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSiteLink extends EditRecord
{
    use TranslatesJsonFields;

    protected static string $resource = SiteLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return ['label' => 'Etiket'];
    }
}
