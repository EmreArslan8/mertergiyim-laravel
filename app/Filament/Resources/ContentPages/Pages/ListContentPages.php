<?php

namespace App\Filament\Resources\ContentPages\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\ContentPages\ContentPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContentPages extends ListRecords
{
    use TranslatesJsonFields;

    protected static string $resource = ContentPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->mutateDataUsing(fn (array $data): array => $this->fillAutomaticTranslationsFor($data, null)),
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
}
