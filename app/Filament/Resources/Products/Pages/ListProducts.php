<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;


class ListProducts extends ListRecords
{
    use TranslatesJsonFields;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Bilerek CreateAction değil: ListRecords, CreateAction'a ürün formunu
            // bağlayıp modal açtırıyor. Düz bağlantı action'ı doğrudan
            // /admin/products/create sayfasına gider. Otomatik çeviri doldurma
            // orada CreateProduct'taki mutateFormDataBeforeCreate() ile yapılır.
            Action::make('create')
                ->label('Yeni ürün ekle')
                ->icon(Heroicon::Plus)
                ->url(fn (): string => ProductResource::getUrl('create')),
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
