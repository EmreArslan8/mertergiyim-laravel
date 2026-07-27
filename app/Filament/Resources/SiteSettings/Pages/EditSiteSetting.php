<?php

namespace App\Filament\Resources\SiteSettings\Pages;

use App\Filament\Resources\SiteSettings\SiteSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditSiteSetting extends EditRecord
{
    protected static string $resource = SiteSettingResource::class;

    /**
     * site_settings.updated_at kolonu Eloquent timestamp'ı olmadığı için
     * elle tazelenir.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_at'] = now();

        return $data;
    }
}
