<?php

namespace Tests\Unit;

use App\Services\Telegram\TelegramPageReader;
use App\Services\Telegram\TelegramProductGrouper;
use PHPUnit\Framework\TestCase;

/**
 * Okuyucu ve gruplayıcı, üç kanaldan indirilmiş gerçek t.me/s sayfalarına
 * karşı çalıştırılır (tests/Fixtures/telegram).
 */
class TelegramPageReaderTest extends TestCase
{
    private TelegramPageReader $reader;

    private TelegramProductGrouper $grouper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reader = new TelegramPageReader;
        $this->grouper = new TelegramProductGrouper;
    }

    private function fixture(string $channel): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/telegram/'.$channel.'.html');
    }

    public function test_mesajlar_id_sirasiyla_okunur(): void
    {
        $messages = $this->reader->messages($this->fixture('asprinntrendy'));

        $this->assertNotEmpty($messages);

        $ids = array_column($messages, 'id');
        $sorted = $ids;
        sort($sorted);

        $this->assertSame($sorted, $ids, 'Mesajlar id sırasına göre gelmeli');
    }

    public function test_en_eski_mesaj_id_sayfalama_icin_bulunur(): void
    {
        $html = $this->fixture('asprinntrendy');

        $this->assertSame(
            min(array_column($this->reader->messages($html), 'id')),
            $this->reader->oldestMessageId($html)
        );
    }

    public function test_asprinntrendy_metin_ve_albumler_tek_urunde_birlesir(): void
    {
        $products = $this->grouper->group($this->reader->messages($this->fixture('asprinntrendy')));

        // Metni olan ürünlerin görselleri peşindeki albümlerden gelmeli.
        $withText = array_values(array_filter($products, fn (array $p): bool => $p['text'] !== ''));

        $this->assertNotEmpty($withText);

        $named = array_values(array_filter($withText, fn (array $p): bool => $p['photo_count'] > 0));

        $this->assertNotEmpty($named, 'Metinli ürünlerin en az biri görselli olmalı');

        // Bu kanalda ürün başına birden çok albüm (renk) geliyor.
        $albumIndexes = array_column($named[0]['media'], 'album_index');

        $this->assertSame(0, min($albumIndexes), 'Albüm sıra numarası sıfırdan başlamalı');
    }

    public function test_naturallover_altyazili_album_tek_urun_olur(): void
    {
        $products = $this->grouper->group($this->reader->messages($this->fixture('naturallover')));

        $withText = array_values(array_filter($products, fn (array $p): bool => $p['text'] !== ''));

        $this->assertNotEmpty($withText);

        foreach ($withText as $product) {
            $this->assertStringContainsString('Code:', $product['text']);
        }
    }

    public function test_indirilemeyen_video_isaretlenir_ve_kapak_karesi_kalir(): void
    {
        $products = $this->grouper->group($this->reader->messages($this->fixture('naturallover')));

        $videos = [];

        foreach ($products as $product) {
            foreach ($product['media'] as $media) {
                if ($media['type'] === 'video') {
                    $videos[] = $media;
                }
            }
        }

        $this->assertNotEmpty($videos, 'Bu kanalda video olmalı');

        $blocked = array_values(array_filter($videos, fn (array $v): bool => ! $v['downloadable']));

        $this->assertNotEmpty($blocked, '20 MB üstü videolar indirilemez olarak işaretlenmeli');

        foreach ($blocked as $video) {
            $this->assertNull($video['source_url'], 'İndirilemeyen videoda mp4 adresi olmamalı');
            $this->assertNotNull($video['poster_url'], 'Kapak karesi her hâlükârda gelmeli');
        }
    }

    public function test_fotograf_adresleri_video_kapaklariyla_karismaz(): void
    {
        foreach (['asprinntrendy', 'naturallover', 'rosearyaa'] as $channel) {
            $products = $this->grouper->group($this->reader->messages($this->fixture($channel)));

            foreach ($products as $product) {
                foreach ($product['media'] as $media) {
                    if ($media['type'] === 'photo') {
                        $this->assertNotNull($media['source_url'], "[$channel] fotoğrafın adresi olmalı");
                    }
                }
            }
        }
    }

    public function test_ters_yonde_gruplama_medyayi_sonraki_metne_baglar(): void
    {
        $messages = [
            ['id' => 10, 'text' => '', 'posted_at' => null, 'photos' => ['a.jpg'], 'videos' => []],
            ['id' => 11, 'text' => 'Keten Elbise', 'posted_at' => null, 'photos' => [], 'videos' => []],
        ];

        $products = $this->grouper->group($messages, TelegramProductGrouper::MEDIA_BEFORE_TEXT);

        $this->assertCount(1, $products);
        $this->assertSame('Keten Elbise', $products[0]['text']);
        $this->assertSame(1, $products[0]['photo_count']);
    }

    public function test_araya_giren_duyuru_urun_acmaz_ve_sonraki_albumu_calmaz(): void
    {
        $messages = [
            // A: sinyalli metin (fiyat+kod) + arkasından foto.
            ['id' => 10, 'text' => 'Keten Takım Kod 7001 Fiyat 20$', 'posted_at' => null, 'photos' => [], 'videos' => []],
            ['id' => 11, 'text' => '', 'posted_at' => null, 'photos' => ['a.jpg'], 'videos' => []],
            // Duyuru: sinyalsiz + işaretli → ürün açmamalı.
            ['id' => 12, 'text' => 'Sipariş için DM atın', 'posted_at' => null, 'photos' => [], 'videos' => []],
            // B: sinyalli metin + arkasından foto.
            ['id' => 13, 'text' => 'Poplin Elbise Kod 7002 Fiyat 22$', 'posted_at' => null, 'photos' => [], 'videos' => []],
            ['id' => 14, 'text' => '', 'posted_at' => null, 'photos' => ['b.jpg'], 'videos' => []],
        ];

        $products = $this->grouper->group($messages);

        $withText = array_values(array_filter($products, fn (array $p): bool => $p['text'] !== ''));

        // Yalnızca iki gerçek ürün; duyuru ürün açmadı.
        $this->assertCount(2, $withText);
        $this->assertStringNotContainsString('Sipariş', $withText[0]['text'].$withText[1]['text']);

        // Her ürünün fotoğrafı kendindedir; duyuru B'nin albümünü A'ya kaptırmadı.
        $this->assertSame(['a.jpg'], array_column($withText[0]['media'], 'source_url'));
        $this->assertSame(['b.jpg'], array_column($withText[1]['media'], 'source_url'));
    }

    public function test_duyurudan_sonraki_oksuz_foto_onceki_urune_yapismaz(): void
    {
        $messages = [
            ['id' => 10, 'text' => 'Poplin Elbise Kod 7001 Fiyat 20$', 'posted_at' => null, 'photos' => [], 'videos' => []],
            ['id' => 11, 'text' => '', 'posted_at' => null, 'photos' => ['a.jpg'], 'videos' => []],
            ['id' => 12, 'text' => 'Kampanya başladı', 'posted_at' => null, 'photos' => [], 'videos' => []],
            // Metni pencere dışında kalmış bir ürünün fotoğrafı gibi: öksüz kalmalı,
            // önceki ürüne yapışmamalı ama sessizce de kaybolmamalı.
            ['id' => 13, 'text' => '', 'posted_at' => null, 'photos' => ['x.jpg'], 'videos' => []],
        ];

        $products = $this->grouper->group($messages);

        $named = array_values(array_filter($products, fn (array $p): bool => $p['text'] !== ''));
        $orphans = array_values(array_filter($products, fn (array $p): bool => $p['text'] === ''));

        // Ürün yalnızca kendi fotoğrafını taşır.
        $this->assertSame(['a.jpg'], array_column($named[0]['media'], 'source_url'));

        // Öksüz foto ayrı adayda durur; kaybolmadı.
        $this->assertSame(['x.jpg'], array_column($orphans[0]['media'], 'source_url'));
    }
}
