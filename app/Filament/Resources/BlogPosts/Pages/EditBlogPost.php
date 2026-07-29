<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    use TranslatesJsonFields;

    protected static string $resource = BlogPostResource::class;

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
            'excerpt' => 'Özet',
            'content' => 'İçerik',
        ];
    }
}
