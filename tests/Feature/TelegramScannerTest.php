<?php

namespace Tests\Feature;

use App\Models\TelegramAccount;
use App\Models\TelegramChannel;
use App\Models\TelegramChannelProduct;
use App\Models\TelegramScan;
use App\Services\Telegram\TelegramScanner;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tarama uçtan uca: sahte t.me sayfası → gruplama → parse → kayıt.
 *
 * Ağa çıkılmaz; gerçek indirilmiş sayfalar Http::fake ile döndürülür.
 */
class TelegramScannerTest extends TestCase
{
    private function fixture(string $channel): string
    {
        return file_get_contents(base_path('tests/Fixtures/telegram/'.$channel.'.html'));
    }

    private function fakeChannel(string $channel): void
    {
        Http::fake([
            't.me/s/'.$channel.'*' => Http::response($this->fixture($channel)),
        ]);
    }

    private function scan(string $channel, int $limit = 100): TelegramScan
    {
        $scan = TelegramScan::create([
            'channels' => [$channel],
            'message_limit' => $limit,
            'status' => 'queued',
        ]);

        return app(TelegramScanner::class)->run($scan);
    }

    public function test_tarama_urunleri_kaydeder_ve_tamamlanir(): void
    {
        $this->fakeChannel('asprinntrendy');

        $scan = $this->scan('asprinntrendy');

        $this->assertSame('completed', $scan->status);
        $this->assertGreaterThan(0, $scan->found_count);
        $this->assertSame($scan->found_count, TelegramChannelProduct::query()->count());

        // İlk taramada bulunan her ürün yeni; bitiş mesajı da onu söyler.
        $this->assertSame($scan->found_count, $scan->new_count);
        $this->assertSame($scan->new_count.' yeni ürün bulundu.', $scan->message);
    }

    public function test_ikinci_taramada_ayni_urunler_yeni_sayilmaz(): void
    {
        $this->fakeChannel('asprinntrendy');

        $first = $this->scan('asprinntrendy');
        $second = $this->scan('asprinntrendy');

        // Aynı mesajlar tekrar okunuyor ama hiçbiri yeni değil.
        $this->assertGreaterThan(0, $first->new_count);
        $this->assertSame(0, $second->new_count);
        $this->assertSame($first->found_count, $second->found_count);
        $this->assertSame('Yeni ürün yok.', $second->message);

        // Ürünler ilk görüldükleri taramaya bağlı kalır.
        $this->assertSame(
            $first->found_count,
            TelegramChannelProduct::query()->where('first_telegram_scan_id', $first->getKey())->count(),
        );
        $this->assertSame(
            0,
            TelegramChannelProduct::query()->where('first_telegram_scan_id', $second->getKey())->count(),
        );
    }

    public function test_tarama_numarasi_sirayla_artar(): void
    {
        $this->fakeChannel('asprinntrendy');

        $first = $this->scan('asprinntrendy');
        $second = $this->scan('asprinntrendy');

        $this->assertSame($first->number + 1, $second->number);
    }

    public function test_ayni_mesaj_tekrar_taranınca_kayit_cogalmaz(): void
    {
        $this->fakeChannel('asprinntrendy');

        $this->scan('asprinntrendy');
        $countAfterFirst = TelegramChannelProduct::query()->count();

        $this->scan('asprinntrendy');

        $this->assertSame($countAfterFirst, TelegramChannelProduct::query()->count());
    }

    public function test_alanlar_metinden_cozulur(): void
    {
        $this->fakeChannel('asprinntrendy');

        $this->scan('asprinntrendy');

        $product = TelegramChannelProduct::query()->whereNotNull('price')->first();

        $this->assertNotNull($product, 'Fiyatı çözülmüş en az bir ürün olmalı');
        $this->assertNotNull($product->name);
        $this->assertSame('channel', $product->name_source);
        $this->assertSame('USD', $product->currency);
        $this->assertNotNull($product->post_url);
        $this->assertNotNull($product->raw_text);
    }

    public function test_naturallover_isimsiz_kaydedilir_uydurulmaz(): void
    {
        $this->fakeChannel('naturallover');

        $this->scan('naturallover');

        $products = TelegramChannelProduct::query()->where('channel', 'naturallover')->get();

        $this->assertNotEmpty($products);

        // Bu kanal ürün adı paylaşmıyor; kod ve fiyat gelmeli, ad boş kalmalı.
        $withCode = $products->whereNotNull('product_code');

        $this->assertNotEmpty($withCode, 'Ürün kodları çözülmeli');
        $this->assertTrue(
            $products->every(fn (TelegramChannelProduct $p): bool => $p->name_source !== 'ai'),
            'Tarama aşamasında AI adı üretilmemeli'
        );
    }

    public function test_medya_kaydedilir_indirilemeyen_video_isaretlenir(): void
    {
        $this->fakeChannel('naturallover');

        $this->scan('naturallover');

        $videos = TelegramChannelProduct::query()
            ->where('channel', 'naturallover')
            ->get()
            ->flatMap(fn (TelegramChannelProduct $p) => $p->images)
            ->where('type', 'video');

        $this->assertNotEmpty($videos, 'Video kayıtları oluşmalı');

        $blocked = $videos->where('downloadable', false);

        $this->assertNotEmpty($blocked, '20 MB üstü videolar işaretlenmeli');
        $this->assertTrue(
            $blocked->every(fn ($v): bool => filled($v->poster_url)),
            'İndirilemeyen videoda kapak karesi kalmalı'
        );
    }

    public function test_tarama_sonrasi_kanal_son_durumu_guncellenir(): void
    {
        $this->fakeChannel('asprinntrendy');

        $this->scan('asprinntrendy');

        $channel = TelegramChannel::query()->where('username', 'asprinntrendy')->first();

        $this->assertNotNull($channel->last_scanned_message_id);
        $this->assertNotNull($channel->last_scanned_at);
    }

    public function test_kataloga_aktarilmis_kayit_yeniden_taramada_bozulmaz(): void
    {
        $this->fakeChannel('asprinntrendy');

        $this->scan('asprinntrendy');

        $product = TelegramChannelProduct::query()->firstOrFail();
        $product->update(['status' => 'imported', 'name' => 'Elle düzeltilmiş ad']);

        $this->scan('asprinntrendy');

        $this->assertSame('Elle düzeltilmiş ad', $product->fresh()->name);
        $this->assertSame('imported', $product->fresh()->status);
    }

    public function test_kataloga_aktarilmis_kayit_yeni_taramaya_baglanir(): void
    {
        $this->fakeChannel('asprinntrendy');

        $ilk = $this->scan('asprinntrendy');

        $product = TelegramChannelProduct::query()->firstOrFail();
        $product->update(['status' => 'imported']);

        $ikinci = $this->scan('asprinntrendy');

        // İçerik korunur ama kayıt "en son bu taramada görüldü" der; yoksa
        // tarama detayındaki "Hepsi" sekmesi bu ürünü hiç listelemiyordu.
        $this->assertSame($ilk->id, $product->fresh()->first_telegram_scan_id);
        $this->assertSame($ikinci->id, $product->fresh()->telegram_scan_id);
        $this->assertSame($ikinci->found_count, $ikinci->products()->count());
    }

    public function test_pencere_disinda_kalan_albumler_hayalet_urun_uretmez(): void
    {
        $this->fakeChannel('asprinntrendy');

        // Küçük limit, kesme noktasının ortaya denk gelmesini sağlar.
        $this->scan('asprinntrendy', 8);

        $ghosts = TelegramChannelProduct::query()
            ->whereNull('name')
            ->whereNull('price')
            ->whereNull('product_code')
            ->get();

        $this->assertCount(0, $ghosts, 'Metni olmayan albümlerden ürün üretilmemeli');
    }

    public function test_ag_hatasi_taramayi_basarisiz_isaretler(): void
    {
        Http::fake(['t.me/*' => Http::response('', 500)]);

        $scan = $this->scan('asprinntrendy');

        // Sayfa alınamadı; tarama çökmemeli, ürün de üretmemeli.
        $this->assertSame('completed', $scan->status);
        $this->assertSame(0, $scan->found_count);
        $this->assertSame(0, TelegramChannelProduct::query()->count());
    }

    public function test_hesap_secilse_de_oturum_yoksa_onizlemeye_dusulur(): void
    {
        $this->fakeChannel('asprinntrendy');

        // Kayit var ama girisi tamamlanmamis: tarama hata vermek yerine
        // hesapsiz yoldan devam etmeli.
        $account = TelegramAccount::create([
            'phone' => '+905060603884',
            'status' => 'new',
        ]);

        $scan = TelegramScan::create([
            'channels' => ['asprinntrendy'],
            'message_limit' => 40,
            'status' => 'queued',
            'telegram_account_id' => $account->getKey(),
        ]);

        $scan = app(TelegramScanner::class)->run($scan);

        $this->assertSame('completed', $scan->status);
        $this->assertSame('preview', $scan->source);
        $this->assertGreaterThan(0, $scan->found_count);
    }
}
