<?php

namespace App\Filament\Resources\HeroSlides\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\HeroSlides\HeroSlideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHeroSlides extends ListRecords
{
    use TranslatesJsonFields;

    protected static string $resource = HeroSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(fn (array $data): array => $this->fillAutomaticTranslationsFor($data, null)),
        ];
    }

    protected function translatableJsonFields(): array
    {
        return [
            'title' => 'Başlık',
            'button_text' => 'Buton Metni',
        ];
    }
}
