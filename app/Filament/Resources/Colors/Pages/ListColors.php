<?php

namespace App\Filament\Resources\Colors\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Colors\ColorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListColors extends ListRecords
{
    use TranslatesJsonFields;

    protected static string $resource = ColorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(fn (array $data): array => $this->fillAutomaticTranslationsFor($data, null)),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return ['name_i18n' => 'Renk adı'];
    }
}
