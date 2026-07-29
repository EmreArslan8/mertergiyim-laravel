<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Media\MediaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMedia extends CreateRecord
{
    use TranslatesJsonFields;

    protected static string $resource = MediaResource::class;

    protected function translatableJsonFields(): array
    {
        return [
            'title' => 'Başlık',
            'description' => 'Açıklama',
        ];
    }
}
