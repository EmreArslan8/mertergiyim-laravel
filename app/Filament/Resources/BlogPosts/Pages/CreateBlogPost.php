<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPost extends CreateRecord
{
    use HasBackToListAction;

    use TranslatesJsonFields;

    protected static string $resource = BlogPostResource::class;

    // Tek buton: "Oluştur". "Oluştur ve yeni ekle" arka arkaya kayıt girilen
    // ekranlar için; bu panelde kayıtlar tek tek açılıyor ve iki buton hangisine
    // basılacağı kararını zorlaştırıyordu.
    protected static bool $canCreateAnother = false;

    protected function translatableJsonFields(): array
    {
        return [
            'title' => 'Başlık',
            'excerpt' => 'Özet',
            'content' => 'İçerik',
        ];
    }

    protected function backToListLabel(): string
    {
        return 'Blog sayfalarına dön';
    }
}
