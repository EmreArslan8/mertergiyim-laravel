<?php

namespace App\Filament\Resources\Sizes\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Sizes\SizeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSizes extends ListRecords
{
    use TranslatesJsonFields;

    protected static string $resource = SizeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(fn (array $data): array => $this->fillAutomaticTranslationsFor($data, null)),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return ['name_i18n' => 'Beden'];
    }
}
