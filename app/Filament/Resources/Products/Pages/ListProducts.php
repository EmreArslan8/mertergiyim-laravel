<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListProducts extends ListRecords
{
    use TranslatesJsonFields;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalHeading('Yeni ürün ekle')
                ->modalWidth(Width::FiveExtraLarge)
                ->extraModalWindowAttributes(['class' => 'merter-product-modal'])
                ->mutateDataUsing(fn (array $data): array => $this->fillAutomaticTranslationsFor($data, null)),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return [
            'name' => 'Ürün Adı',
            'description' => 'Açıklama',
        ];
    }
}
