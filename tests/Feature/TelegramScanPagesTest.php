<?php

namespace Tests\Feature;

use App\Filament\Pages\TelegramScanDetail;
use App\Filament\Pages\TelegramScans;
use App\Models\TelegramAccount;
use App\Models\TelegramChannelProduct;
use App\Models\TelegramScan;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Ürün Çek" ekranı ve tarama detay sayfası.
 */
class TelegramScanPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    private function fakeChannel(string $channel): void
    {
        Http::fake([
            't.me/s/'.$channel.'*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/telegram/'.$channel.'.html'))
            ),
        ]);
    }

    public function test_urun_cek_ekrani_kanallari_listeler(): void
    {
        Livewire::test(TelegramScans::class)
            ->assertOk()
            ->assertSee('Yeni Tarama')
            ->assertSee('@asprinntrendy')
            ->assertSee('@naturallover')
            ->assertSee('@rosearyaa');
    }

    public function test_kanallar_varsayilan_olarak_secili_gelir(): void
    {
        $selected = Livewire::test(TelegramScans::class)->get('selectedChannels');

        $this->assertContains('asprinntrendy', $selected);
        $this->assertContains('naturallover', $selected);
    }

    public function test_kanal_secilmeden_tarama_baslamaz(): void
    {
        Livewire::test(TelegramScans::class)
            ->set('selectedChannels', [])
            ->call('startScan');

        $this->assertSame(0, TelegramScan::query()->count());
    }

    public function test_urun_cek_tarama_olusturur_ve_detaya_yonlendirir(): void
    {
        $this->fakeChannel('asprinntrendy');

        // startScan yalnızca kaydı açar; tarama tick ile kanal kanal ilerler.
        $page = Livewire::test(TelegramScans::class)
            ->set('selectedChannels', ['asprinntrendy'])
            ->set('messageLimit', 40)
            ->call('startScan');

        $scan = TelegramScan::query()->firstOrFail();

        $this->assertSame('running', $scan->status);
        $this->assertSame(0, $scan->cursor);

        // Tek kanal seçili: bir tick taramayı bitirir ve detaya yönlendirir.
        $page->call('tick')->assertRedirect();

        $scan->refresh();

        $this->assertSame('completed', $scan->status);
        $this->assertSame(40, $scan->message_limit);
        $this->assertGreaterThan(0, $scan->found_count);
        $this->assertSame($scan->found_count, $scan->new_count);
    }

    public function test_gecmis_tablosunda_tarama_gorunur(): void
    {
        TelegramScan::create([
            'channels' => ['naturallover'],
            'message_limit' => 100,
            'status' => 'completed',
            'message' => 'Tarama bitti.',
            'found_count' => 7,
            'finished_at' => now(),
        ]);

        Livewire::test(TelegramScans::class)
            ->assertSee('@naturallover')
            ->assertSee('Tarama bitti.')
            ->assertSee('Tamamlandı');
    }

    public function test_detay_sayfasi_ozet_ve_urunleri_gosterir(): void
    {
        $scan = TelegramScan::create([
            'channels' => ['asprinntrendy'],
            'message_limit' => 100,
            'status' => 'completed',
            'message' => 'Tarama bitti.',
            'found_count' => 1,
            'finished_at' => now(),
        ]);

        TelegramChannelProduct::create([
            'telegram_scan_id' => $scan->id,
            'first_telegram_scan_id' => $scan->id,
            'channel' => 'asprinntrendy',
            'message_id' => 48320,
            'name' => 'Krınkıl Keten Takım',
            'price' => 22,
            'currency' => 'USD',
            'size_series' => '2s 2m 1l',
            'status' => 'new',
        ]);

        Livewire::test(TelegramScanDetail::class, ['record' => $scan->getKey()])
            ->assertOk()
            ->assertSee('Tarama #'.$scan->number)
            ->assertSee('Krınkıl Keten Takım')
            ->assertSee('22 USD')
            ->assertSee('Hızlı Ekle');
    }

    public function test_detayda_eksik_alanlar_uyari_olarak_yazilir(): void
    {
        $scan = TelegramScan::create([
            'channels' => ['naturallover'],
            'message_limit' => 100,
            'status' => 'completed',
            'found_count' => 1,
        ]);

        // @naturallover ürün adı, beden ve renk paylaşmıyor.
        TelegramChannelProduct::create([
            'telegram_scan_id' => $scan->id,
            'first_telegram_scan_id' => $scan->id,
            'channel' => 'naturallover',
            'message_id' => 18357,
            'product_code' => '6203',
            'price' => 6,
            'currency' => 'USD',
            'status' => 'new',
        ]);

        Livewire::test(TelegramScanDetail::class, ['record' => $scan->getKey()])
            ->assertSee('Ürün adı eksik')
            ->assertSee('Paket bedenleri eksik')
            ->assertSee('Renk adı çekilemedi')
            ->assertSee('Kategori eksik');
    }

    public function test_tarama_kanal_kanal_ilerler_ve_ilerleme_gosterilir(): void
    {
        $this->fakeChannel('asprinntrendy');
        $this->fakeChannel('rosearyaa');

        $page = Livewire::test(TelegramScans::class)
            ->set('selectedChannels', ['asprinntrendy', 'rosearyaa'])
            ->call('startScan');

        $scan = TelegramScan::query()->firstOrFail();

        // Başlangıçta hiçbir kanal işlenmemiş: kullanıcı boş ekrana bakmıyor,
        // ilerleme çubuğu %0'da duruyor.
        $this->assertSame(0, $scan->progressPercent());
        $page->assertSee('0/2 kanal');

        // İlk tick tek kanal işler, tarama sürmeye devam eder.
        $page->call('tick');
        $scan->refresh();

        $this->assertSame(1, $scan->cursor);
        $this->assertSame('running', $scan->status);
        $this->assertSame(50, $scan->progressPercent());

        // İkinci tick kalan kanalı bitirir ve detaya yönlendirir.
        $page->call('tick')->assertRedirect();
        $scan->refresh();

        $this->assertSame('completed', $scan->status);
        $this->assertSame(100, $scan->progressPercent());
        $this->assertGreaterThan(0, $scan->new_count);
    }

    public function test_detay_tek_listede_taramada_gorulen_tum_urunleri_gosterir(): void
    {
        $onceki = TelegramScan::create([
            'channels' => ['asprinntrendy'],
            'message_limit' => 100,
            'status' => 'completed',
        ]);

        $scan = TelegramScan::create([
            'channels' => ['asprinntrendy'],
            'message_limit' => 100,
            'status' => 'completed',
            'found_count' => 2,
            'new_count' => 1,
        ]);

        // Önceki taramada çıkmış, bu taramada tekrar görülmüş ürün.
        TelegramChannelProduct::create([
            'telegram_scan_id' => $scan->id,
            'first_telegram_scan_id' => $onceki->id,
            'channel' => 'asprinntrendy',
            'message_id' => 48100,
            'name' => 'Eskiden Cekilen Takim',
            'status' => 'new',
        ]);

        TelegramChannelProduct::create([
            'telegram_scan_id' => $scan->id,
            'first_telegram_scan_id' => $scan->id,
            'channel' => 'asprinntrendy',
            'message_id' => 48320,
            'name' => 'Bu Taramada Cikan Takim',
            'status' => 'new',
        ]);

        // Tek liste: taramada görülen tüm ürünler birlikte gösterilir.
        // Hangisinin bu taramada çıktığı kartta rozetle yazar (durum
        // rozetindeki "Yeni" ile karışmasın).
        Livewire::test(TelegramScanDetail::class, ['record' => $scan->getKey()])
            ->assertOk()
            ->assertSee('Bu Taramada Cikan Takim')
            ->assertSee('Eskiden Cekilen Takim')
            ->assertSee('Bu taramada çıktı')
            ->assertSee('Daha önce çekilmişti');
    }

    public function test_detay_aramayla_urun_listesini_daraltir(): void
    {
        $scan = TelegramScan::create([
            'channels' => ['asprinntrendy'],
            'message_limit' => 100,
            'status' => 'completed',
            'found_count' => 2,
            'new_count' => 2,
        ]);

        TelegramChannelProduct::create([
            'telegram_scan_id' => $scan->id,
            'first_telegram_scan_id' => $scan->id,
            'channel' => 'asprinntrendy',
            'message_id' => 48400,
            'name' => 'Kirmizi Elbise',
            'status' => 'new',
        ]);

        TelegramChannelProduct::create([
            'telegram_scan_id' => $scan->id,
            'first_telegram_scan_id' => $scan->id,
            'channel' => 'asprinntrendy',
            'message_id' => 48401,
            'name' => 'Mavi Gomlek',
            'status' => 'new',
        ]);

        Livewire::test(TelegramScanDetail::class, ['record' => $scan->getKey()])
            ->assertSee('Kirmizi Elbise')
            ->assertSee('Mavi Gomlek')
            ->set('search', 'Kirmizi')
            ->assertSee('Kirmizi Elbise')
            ->assertDontSee('Mavi Gomlek');
    }

    public function test_detay_eklenme_durumu_ve_fiyata_gore_daraltir(): void
    {
        $scan = TelegramScan::create([
            'channels' => ['asprinntrendy'],
            'message_limit' => 100,
            'status' => 'completed',
            'found_count' => 2,
            'new_count' => 2,
        ]);

        // Kataloğa aktarılmış ürün.
        TelegramChannelProduct::create([
            'telegram_scan_id' => $scan->id,
            'first_telegram_scan_id' => $scan->id,
            'channel' => 'asprinntrendy',
            'message_id' => 48500,
            'name' => 'Eklenmis Takim',
            'status' => 'imported',
            'price' => 30,
        ]);

        // Henüz eklenmemiş ürün.
        TelegramChannelProduct::create([
            'telegram_scan_id' => $scan->id,
            'first_telegram_scan_id' => $scan->id,
            'channel' => 'asprinntrendy',
            'message_id' => 48501,
            'name' => 'Bekleyen Takim',
            'status' => 'new',
            'price' => 10,
        ]);

        $page = Livewire::test(TelegramScanDetail::class, ['record' => $scan->getKey()]);

        // "Kataloğa eklenmemiş" yalnızca bekleyeni gösterir.
        $page->set('addedFilter', 'no')
            ->assertSee('Bekleyen Takim')
            ->assertDontSee('Eklenmis Takim');

        // "Kataloğa eklenmiş" yalnızca aktarılanı gösterir.
        $page->set('addedFilter', 'yes')
            ->assertSee('Eklenmis Takim')
            ->assertDontSee('Bekleyen Takim');

        // Fiyat artan sıralamada ucuz olan önce gelir.
        $page->set('addedFilter', '')
            ->set('sort', 'price_asc');

        $ucuzOnce = strpos($page->html(), 'Bekleyen Takim') < strpos($page->html(), 'Eklenmis Takim');
        $this->assertTrue($ucuzOnce, 'Fiyat artan sıralamada ucuz ürün önce gelmeli.');
    }

    public function test_yarida_kalan_tarama_sayfa_acilinca_devam_eder(): void
    {
        $this->fakeChannel('asprinntrendy');
        $this->fakeChannel('rosearyaa');

        // Kullanıcı tarama sürerken sayfayı kapatmış: kayıt "çalışıyor"da,
        // ilk kanal işlenmiş.
        $scan = TelegramScan::create([
            'channels' => ['asprinntrendy', 'rosearyaa'],
            'message_limit' => 100,
            'status' => 'running',
            'cursor' => 1,
            'found_count' => 5,
            'new_count' => 5,
        ]);

        $page = Livewire::test(TelegramScans::class);

        // Sayfa açılır açılmaz süren taramayı devralır.
        $page->assertSet('runningScanId', $scan->getKey())
            ->assertSee('1/2 kanal');

        $page->call('tick')->assertRedirect();

        $scan->refresh();

        $this->assertSame('completed', $scan->status);
        // İlk kanal tekrar taranmadı: imleç kaldığı yerden ilerledi.
        $this->assertSame(2, $scan->cursor);
    }

    public function test_detayda_foto_ve_video_buyutucuyle_gosterilir(): void
    {
        $scan = TelegramScan::create([
            'channels' => ['rosearyaa'],
            'message_limit' => 100,
            'status' => 'completed',
            'found_count' => 1,
            'new_count' => 1,
        ]);

        $product = TelegramChannelProduct::create([
            'telegram_scan_id' => $scan->id,
            'first_telegram_scan_id' => $scan->id,
            'channel' => 'rosearyaa',
            'message_id' => 2542,
            'name' => 'Keten Takim',
            'post_url' => 'https://t.me/rosearyaa/2542',
            'status' => 'new',
        ]);

        $product->images()->createMany([
            [
                'type' => 'photo',
                'source_url' => 'https://cdn4.telesco.pe/file/foto-1',
                'album_index' => 0,
                'sort_order' => 0,
            ],
            [
                'type' => 'video',
                'source_url' => 'https://cdn4.telesco.pe/file/video-1.mp4',
                'poster_url' => 'https://cdn4.telesco.pe/file/kapak-1',
                'duration' => '0:32',
                'downloadable' => true,
                'album_index' => 0,
                'sort_order' => 1,
            ],
            [
                // 20 MB ustu video: Telegram dosyayi vermiyor, elde kapak kaliyor.
                'type' => 'video',
                'source_url' => null,
                'poster_url' => 'https://cdn4.telesco.pe/file/kapak-2',
                'duration' => '1:10',
                'downloadable' => false,
                'album_index' => 0,
                'sort_order' => 2,
            ],
        ]);

        Livewire::test(TelegramScanDetail::class, ['record' => $scan->getKey()])
            ->assertOk()
            ->assertSee('Keten Takim')
            // Buyutucu verisi sayfaya gomulmus olmali. @js() JSON urettigi
            // icin yoldaki bolu isaretleri kacirilmis halde basiliyor.
            ->assertSee('foto-1', false)
            ->assertSee('video-1.mp4', false)
            ->assertSee('kapak-2', false)
            ->assertSee('0:32')
            ->assertSee('merter-tg-lightbox', false);
    }

    public function test_hesap_yokken_uyari_gosterilir_ve_onizlemeyle_cekilir(): void
    {
        $this->fakeChannel('asprinntrendy');

        $page = Livewire::test(TelegramScans::class)
            ->assertSee('Bağlı Telegram hesabı yok')
            ->assertSet('accountId', null);

        $page->set('selectedChannels', ['asprinntrendy'])
            ->call('startScan');

        $scan = TelegramScan::query()->firstOrFail();

        $this->assertNull($scan->telegram_account_id);
        $this->assertSame('preview', $scan->source);
    }

    public function test_bagli_hesap_varsa_uyari_cikmaz_ve_hesap_secilir(): void
    {
        $account = TelegramAccount::create([
            'label' => 'Merter ana hat',
            'phone' => '+905060603884',
            'status' => 'active',
        ]);

        Livewire::test(TelegramScans::class)
            ->assertDontSee('Bağlı Telegram hesabı yok')
            // Bagli hesap varsayilan olarak secili gelir.
            ->assertSet('accountId', $account->getKey());
    }

    public function test_girisi_tamamlanmamis_hesap_secilmez(): void
    {
        TelegramAccount::create([
            'label' => 'Yeni hat',
            'phone' => '+905321112233',
            'status' => 'new',
        ]);

        Livewire::test(TelegramScans::class)
            ->assertSee('Bağlı Telegram hesabı yok')
            ->assertSet('accountId', null);
    }
}
