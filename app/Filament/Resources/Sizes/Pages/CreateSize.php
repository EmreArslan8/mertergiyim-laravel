<?php

namespace App\Filament\Resources\Sizes\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Sizes\SizeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSize extends CreateRecord
{
    use TranslatesJsonFields;

    protected static string $resource = SizeResource::class;

    protected function translatableJsonFields(): array
    {
        return ['name_i18n' => 'Beden'];
    }
}
