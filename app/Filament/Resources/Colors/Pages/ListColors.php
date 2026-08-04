<?php

namespace App\Filament\Resources\Colors\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Colors\ColorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListColors extends ListRecords
{
    use TranslatesJsonFields;

    protected static string $resource = ColorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Renk ekle')
                ->modalHeading('Yeni renk ekle')
                ->modalWidth(Width::TwoExtraLarge)
                ->extraModalWindowAttributes(['class' => 'merter-color-modal'])
                ->mutateDataUsing(fn (array $data): array => $this->fillAutomaticTranslationsFor($data, null)),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return ['name_i18n' => 'Renk adı'];
    }
}
