<?php

namespace App\Filament\Resources\HeroSlides\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\HeroSlides\HeroSlideResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHeroSlide extends CreateRecord
{
    use HasBackToListAction;
    use TranslatesJsonFields;

    protected static string $resource = HeroSlideResource::class;

    // Tek buton: "Oluştur". "Oluştur ve yeni ekle" arka arkaya kayıt girilen
    // ekranlar için; bu panelde kayıtlar tek tek açılıyor ve iki buton hangisine
    // basılacağı kararını zorlaştırıyordu.
    protected static bool $canCreateAnother = false;

    protected function translatableJsonFields(): array
    {
        return [
            'eyebrow' => 'Üst yazı',
            'title' => 'Başlık',
            'button_text' => 'Buton Metni',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function backToListLabel(): string
    {
        return 'Slider\'a dön';
    }
}
