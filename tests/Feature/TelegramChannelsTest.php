<?php

namespace Tests\Feature;

use App\Filament\Resources\TelegramChannels\Pages\ListTelegramChannels;
use App\Models\TelegramChannel;
use App\Models\TelegramChannelProduct;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kanal ekleme / çıkarma ekranı.
 */
class TelegramChannelsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    public function test_kanallar_listelenir(): void
    {
        Livewire::test(ListTelegramChannels::class)
            ->assertOk()
            ->assertSee('@asprinntrendy')
            ->assertSee('@rosearyaa');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function adresProvider(): array
    {
        return [
            'at isaretli' => ['@yenikanal'],
            'tam adres' => ['https://t.me/yenikanal'],
            'onizleme adresi' => ['https://t.me/s/yenikanal'],
            'sade' => ['yenikanal'],
        ];
    }

    #[DataProvider('adresProvider')]
    public function test_yapistirilan_adres_sade_kullanici_adina_indirgenir(string $girdi): void
    {
        Livewire::test(ListTelegramChannels::class)
            ->callAction('create', ['username' => $girdi, 'title' => 'Yeni Kanal', 'active' => true]);

        $this->assertDatabaseHas('telegram_channels', ['username' => 'yenikanal']);
    }

    public function test_ayni_kanal_ikinci_kez_eklenemez(): void
    {
        Livewire::test(ListTelegramChannels::class)
            ->callAction('create', ['username' => '@asprinntrendy', 'active' => true])
            ->assertHasActionErrors(['username']);

        $this->assertSame(1, TelegramChannel::query()->where('username', 'asprinntrendy')->count());
    }

    public function test_kanal_silinince_cekilmis_urunler_kalir(): void
    {
        $channel = TelegramChannel::query()->where('username', 'asprinntrendy')->firstOrFail();

        $product = TelegramChannelProduct::create([
            'channel' => 'asprinntrendy',
            'message_id' => 12345,
            'name' => 'Kalici Urun',
            'status' => 'new',
        ]);

        $channel->delete();

        // Ürünler kanal kaydına foreign key ile bağlı değil.
        $this->assertDatabaseMissing('telegram_channels', ['id' => $channel->getKey()]);
        $this->assertDatabaseHas('telegram_channel_products', ['id' => $product->getKey()]);
    }
}
