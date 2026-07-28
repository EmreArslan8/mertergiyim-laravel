<?php

namespace App\Filament\Resources\Colors\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Colors\ColorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditColor extends EditRecord
{
    use TranslatesJsonFields;

    protected static string $resource = ColorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return ['name_i18n' => 'Renk adı'];
    }
}
