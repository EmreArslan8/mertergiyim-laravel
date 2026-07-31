<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\Media\MediaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMedia extends CreateRecord
{
    use HasBackToListAction;

    use TranslatesJsonFields;

    protected static string $resource = MediaResource::class;

    // Tek buton: "Oluştur". "Oluştur ve yeni ekle" arka arkaya kayıt girilen
    // ekranlar için; bu panelde kayıtlar tek tek açılıyor ve iki buton hangisine
    // basılacağı kararını zorlaştırıyordu.
    protected static bool $canCreateAnother = false;

    protected function translatableJsonFields(): array
    {
        return [
            'title' => 'Başlık',
            'description' => 'Açıklama',
        ];
    }

    protected function backToListLabel(): string
    {
        return 'Multimedyaya dön';
    }
}
