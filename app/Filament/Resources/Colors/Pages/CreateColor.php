<?php

namespace App\Filament\Resources\Colors\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Colors\ColorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateColor extends CreateRecord
{
    use TranslatesJsonFields;

    protected static string $resource = ColorResource::class;

    protected function translatableJsonFields(): array
    {
        return ['name_i18n' => 'Renk adı'];
    }
}
