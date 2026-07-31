<?php

namespace App\Filament\Resources\HeroSlides\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\HeroSlides\HeroSlideResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHeroSlide extends EditRecord
{
    use HasBackToListAction;

    use TranslatesJsonFields;

    protected static string $resource = HeroSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return [
            'title' => 'Başlık',
            'button_text' => 'Buton Metni',
        ];
    }

    protected function backToListLabel(): string
    {
        return 'Slider\'a dön';
    }
}
