<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use HasBackToListAction;
    use TranslatesJsonFields;

    protected static string $resource = ProductResource::class;

    // Tek buton: "Oluştur". "Oluştur ve yeni ekle" arka arkaya kayıt girilen
    // ekranlar için; bu panelde kayıtlar tek tek açılıyor ve iki buton hangisine
    // basılacağı kararını zorlaştırıyordu.
    protected static bool $canCreateAnother = false;

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
