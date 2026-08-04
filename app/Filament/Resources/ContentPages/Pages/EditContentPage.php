<?php

namespace App\Filament\Resources\ContentPages\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\ContentPages\ContentPageResource;
use App\Filament\Support\Multilingual;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContentPage extends EditRecord
{
    use HasBackToListAction;

    use TranslatesJsonFields;

    protected static string $resource = ContentPageResource::class;

    // Başlık jenerik "Bilgilendirme Sayfası düzenle" yerine düzenlenen sayfanın
    // adını göstersin (ör. "Hakkımızda").
    public function getTitle(): string
    {
        return Multilingual::tr($this->getRecord()->title);
    }

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
