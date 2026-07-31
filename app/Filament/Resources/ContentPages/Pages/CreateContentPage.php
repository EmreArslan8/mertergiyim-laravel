<?php

namespace App\Filament\Resources\ContentPages\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\ContentPages\ContentPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContentPage extends CreateRecord
{
    use HasBackToListAction;

    use TranslatesJsonFields;

    protected static string $resource = ContentPageResource::class;

    // Tek buton: "Oluştur". "Oluştur ve yeni ekle" arka arkaya kayıt girilen
    // ekranlar için; bu panelde kayıtlar tek tek açılıyor ve iki buton hangisine
    // basılacağı kararını zorlaştırıyordu.
    protected static bool $canCreateAnother = false;

    protected function translatableJsonFields(): array
    {
        return [
            'title' => 'Başlık',
            'content' => 'İçerik',
            'seo_title' => 'SEO başlığı',
            'seo_description' => 'SEO açıklaması',
        ];
    }

    protected function backToListLabel(): string
    {
        return 'Sayfalara dön';
    }
}
