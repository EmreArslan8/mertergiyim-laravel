<?php

namespace App\Filament\Resources\ContentPages\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\ContentPages\ContentPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContentPage extends CreateRecord
{
    use TranslatesJsonFields;

    protected static string $resource = ContentPageResource::class;

    protected function translatableJsonFields(): array
    {
        return [
            'title' => 'Başlık',
            'content' => 'İçerik',
            'seo_title' => 'SEO başlığı',
            'seo_description' => 'SEO açıklaması',
        ];
    }
}
