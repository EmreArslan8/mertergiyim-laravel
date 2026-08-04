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

    /**
     * Kaydetme anında yönlendirilmez: "kaydedildi" bildirimi görüldükten
     * sonra listeye SPA geçişiyle dönülür (sipariş detayıyla aynı davranış).
     */
    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    protected function afterSave(): void
    {
        $this->js(sprintf(
            'setTimeout(() => Livewire.navigate(%s), 1000)',
            json_encode(static::getResource()::getUrl('index'), JSON_THROW_ON_ERROR),
        ));
    }

    protected function translatableJsonFields(): array
    {
        return [
            'eyebrow' => 'Üst yazı',
            'title' => 'Başlık',
            'button_text' => 'Buton Metni',
        ];
    }

    protected function backToListLabel(): string
    {
        return 'Slider\'a dön';
    }
}
