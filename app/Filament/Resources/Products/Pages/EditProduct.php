<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use HasBackToListAction;
    use TranslatesJsonFields;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function backToListLabel(): string
    {
        return 'Ürünlere dön';
    }

    protected function translatableJsonFields(): array
    {
        return [
            'name' => 'Ürün Adı',
            'description' => 'Açıklama',
        ];
    }
}
