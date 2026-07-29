<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPost extends CreateRecord
{
    use TranslatesJsonFields;

    protected static string $resource = BlogPostResource::class;

    protected function translatableJsonFields(): array
    {
        return [
            'title' => 'Başlık',
            'excerpt' => 'Özet',
            'content' => 'İçerik',
        ];
    }
}
