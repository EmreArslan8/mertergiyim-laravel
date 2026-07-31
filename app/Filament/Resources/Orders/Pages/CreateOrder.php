<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderItems;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    use HasBackToListAction;

    protected static string $resource = OrderResource::class;

    // Tek buton: "Oluştur". "Oluştur ve yeni ekle" arka arkaya kayıt girilen
    // ekranlar için var; sipariş elle nadiren açılıyor, iki buton kararı
    // zorlaştırıyordu.
    protected static bool $canCreateAnother = false;

    /**
     * "Oluştur" butonu ekranın altına sabitlenir.
     *
     * Sipariş formu uzun (künye + kalemler + müşteri); mobilde kaydetmek için
     * her seferinde en alta inmek gerekiyordu. Butonlar görünür alana girince
     * Filament sabitlemeyi kendiliğinden bırakıyor.
     */
    public function areFormActionsSticky(): bool
    {
        return true;
    }

    /**
     * Toplam formda girilmez: kalemler kaydedildikten sonra onlardan türetilir.
     * Elle girilen toplam, kalemler değişince sessizce yanlışa dönüyordu.
     */
    protected function afterCreate(): void
    {
        OrderItems::recalculateTotal($this->getRecord());
    }

    /**
     * Kaydettikten sonra sipariş listesine dönülür.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function backToListLabel(): string
    {
        return 'Siparişlere dön';
    }
}
