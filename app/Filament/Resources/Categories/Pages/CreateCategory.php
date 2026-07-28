<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use TranslatesJsonFields;

    protected static string $resource = CategoryResource::class;

    protected function translatableJsonFields(): array
    {
        return ['name_i18n' => 'Kategori adı'];
    }
}
