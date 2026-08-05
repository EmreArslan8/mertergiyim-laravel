<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\AdminUsers\AdminUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminUser extends CreateRecord
{
    use HasBackToListAction;

    protected static string $resource = AdminUserResource::class;

    // Tek buton: "Oluştur". "Oluştur ve yeni ekle" arka arkaya kayıt girilen
    // ekranlar için; bu panelde kayıtlar tek tek açılıyor ve iki buton hangisine
    // basılacağı kararını zorlaştırıyordu.
    protected static bool $canCreateAnother = false;

    protected function backToListLabel(): string
    {
        return 'Kullanıcılara dön';
    }

    // Kayıt oluşturulduktan sonra düzenleme ekranına değil, yönetici listesine dön.
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
