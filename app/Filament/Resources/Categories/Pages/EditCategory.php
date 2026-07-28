<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    use TranslatesJsonFields;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return ['name_i18n' => 'Kategori adı'];
    }
}
