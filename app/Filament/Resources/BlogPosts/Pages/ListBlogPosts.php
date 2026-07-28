<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlogPosts extends ListRecords
{
    use TranslatesJsonFields;

    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(fn (array $data): array => $this->fillAutomaticTranslationsFor($data, null))];
    }

    protected function translatableJsonFields(): array
    {
        return ['title' => 'Başlık', 'excerpt' => 'Özet', 'content' => 'İçerik'];
    }
}
