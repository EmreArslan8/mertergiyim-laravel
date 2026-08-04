<?php

namespace Tests\Feature;

use App\Filament\Resources\TelegramChannelProducts\Pages\ListTelegramChannelProducts;
use App\Models\TelegramChannelProduct;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Telegram kanallarından çekilen ürünlerin liste ekranı.
 *
 * Kanallar farklı şemalarda paylaşım yapıyor: birinde ürün adı hiç yok,
 * birinde fiyat yok. Liste bu eksik kayıtlarda da patlamamalı ve eksiği
 * görünür kılmalı.
 */
class TelegramChannelProductsTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    public function test_full_record_renders_with_all_columns(): void
    {
        $record = TelegramChannelProduct::create([
            'channel' => 'asprinntrendy',
            'message_id' => 48320,
            'post_url' => 'https://t.me/asprinntrendy/48320',
            'name' => 'Krınkıl Keten Kumaş İkili Takım',
            'name_source' => 'channel',
            'product_code' => '7963',
            'price' => 22,
            'currency' => 'USD',
            'size_series' => 'Seri 5 li 2s 2m 1l',
            'colors' => ['Natur', 'Haki', 'Beyaz'],
            'status' => 'new',
        ]);

        $record->images()->create([
            'message_id' => 48321,
            'album_index' => 0,
            'sort_order' => 0,
            'source_url' => 'https://cdn4.telesco.pe/file/ornek.jpg',
        ]);

        Livewire::test(ListTelegramChannelProducts::class)
            ->assertOk()
            ->assertSee('Krınkıl Keten Kumaş İkili Takım')
            ->assertSee('AsprinTrendy')
            ->assertSee('22 USD')
            ->assertSee('Natur, Haki, Beyaz')
            ->assertSee('Bekliyor');
    }

    public function test_records_without_name_or_price_still_render(): void
    {
        // @naturallover ürün adı paylaşmıyor, @rosearyaa fiyat paylaşmıyor.
        TelegramChannelProduct::create([
            'channel' => 'naturallover',
            'message_id' => 18357,
            'product_code' => '6203',
            'price' => 6,
            'currency' => 'USD',
            'status' => 'new',
        ]);

        TelegramChannelProduct::create([
            'channel' => 'rosearyaa',
            'message_id' => 2515,
            'name' => 'Keten maxi elbise',
            'name_source' => 'channel',
            'size_series' => 'S2m2L1',
            'status' => 'new',
        ]);

        Livewire::test(ListTelegramChannelProducts::class)
            ->assertOk()
            ->assertSee('— isim yok —')
            ->assertSee('Keten maxi elbise');
    }

    public function test_search_filter_matches_name_and_code(): void
    {
        TelegramChannelProduct::create([
            'channel' => 'naturallover',
            'message_id' => 18357,
            'name' => 'Kot etek',
            'product_code' => '6203',
            'status' => 'new',
        ]);

        TelegramChannelProduct::create([
            'channel' => 'rosearyaa',
            'message_id' => 2515,
            'name' => 'Keten maxi elbise',
            'status' => 'new',
        ]);

        // Kod sütunu listede yok; kodla arayınca ilgili ürün adı gelmeli.
        Livewire::test(ListTelegramChannelProducts::class)
            ->filterTable('search', ['term' => '6203'])
            ->assertSee('Kot etek')
            ->assertDontSee('Keten maxi elbise');

        Livewire::test(ListTelegramChannelProducts::class)
            ->filterTable('search', ['term' => 'keten'])
            ->assertSee('Keten maxi elbise')
            ->assertDontSee('Kot etek');
    }

    public function test_sort_filter_reorders_the_list(): void
    {
        TelegramChannelProduct::create([
            'channel' => 'naturallover',
            'message_id' => 18357,
            'name' => 'Eski kazak',
            'status' => 'new',
            'posted_at' => now()->subDays(3),
        ]);

        TelegramChannelProduct::create([
            'channel' => 'rosearyaa',
            'message_id' => 2515,
            'name' => 'Yeni kazak',
            'status' => 'new',
            'posted_at' => now(),
        ]);

        Livewire::test(ListTelegramChannelProducts::class)
            ->assertSeeInOrder(['Yeni kazak', 'Eski kazak'])
            ->filterTable('sort', 'posted_at_asc')
            ->assertSeeInOrder(['Eski kazak', 'Yeni kazak']);
    }

    public function test_ai_generated_names_are_marked(): void
    {
        // Görselden üretilen isimler kanaldan gelenlerle karışmamalı.
        TelegramChannelProduct::create([
            'channel' => 'naturallover',
            'message_id' => 18357,
            'name' => 'V Yaka Fitilli Triko Kazak',
            'name_source' => 'ai',
            'status' => 'enriched',
        ]);

        Livewire::test(ListTelegramChannelProducts::class)
            ->assertSee('V Yaka Fitilli Triko Kazak')
            ->assertSee('görselden üretildi')
            ->assertSee('Zenginleştirildi');
    }
}
