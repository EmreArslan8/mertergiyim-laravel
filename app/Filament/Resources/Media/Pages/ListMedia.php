<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Media\MediaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMedia extends ListRecords
{
    use TranslatesJsonFields;

    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(fn (array $data): array => $this->fillAutomaticTranslationsFor($data, null))];
    }

    protected function translatableJsonFields(): array
    {
        return ['title' => 'Başlık', 'alt' => 'Alternatif metin', 'caption' => 'Açıklama'];
    }
}
