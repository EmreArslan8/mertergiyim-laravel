<?php

namespace App\Filament\Resources\SiteLinks\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\SiteLinks\SiteLinkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSiteLink extends CreateRecord
{
    use TranslatesJsonFields;

    protected static string $resource = SiteLinkResource::class;

    protected function translatableJsonFields(): array
    {
        return ['label' => 'Etiket'];
    }
}
