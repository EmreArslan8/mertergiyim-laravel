<?php

namespace App\Filament\Resources\Sizes\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Sizes\SizeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSize extends EditRecord
{
    use TranslatesJsonFields;

    protected static string $resource = SizeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return ['name_i18n' => 'Beden'];
    }
}
