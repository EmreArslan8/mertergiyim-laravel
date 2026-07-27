<?php

namespace App\Filament\Resources\HeroSlides\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\HeroSlides\HeroSlideResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHeroSlide extends CreateRecord
{
    use TranslatesJsonFields;

    protected static string $resource = HeroSlideResource::class;

    protected function translatableJsonFields(): array
    {
        return [
            'title' => 'Başlık',
            'button_text' => 'Buton Metni',
        ];
    }
}
