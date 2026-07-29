<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Media\MediaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMedia extends EditRecord
{
    use TranslatesJsonFields;

    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return [
            'title' => 'Başlık',
            'description' => 'Açıklama',
        ];
    }
}
