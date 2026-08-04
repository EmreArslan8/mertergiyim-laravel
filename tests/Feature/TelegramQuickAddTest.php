<?php

namespace Tests\Feature;

use App\Filament\Pages\TelegramScanDetail;
use App\Livewire\TelegramNameSuggester;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Models\TelegramChannelProduct;
use App\Models\TelegramScan;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Hızlı Ekle": Telegram ürün adayının kataloğa aktarılması.
 */
class TelegramQuickAddTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());

        // Telegram CDN'i taklit et: gercek bir JPEG donsun ki ImageUploader
        // isleyebilsin.
        Http::fake([
            'cdn*.telesco.pe/*' => Http::response($this->jpeg(), 200, ['Content-Type' => 'image/jpeg']),
        ]);
    }

    private function jpeg(): string
    {
        $image = imagecreatetruecolor(600, 800);
        ob_start();
        imagejpeg($image);
        $data = (string) ob_get_clean();
        imagedestroy($image);

        return $data;
    }

    private function candidate(): TelegramChannelProduct
    {
        $scan = TelegramScan::create([
            'channels' => ['asprinntrendy'],
            'message_limit' => 100,
            'status' => 'completed',
            'found_count' => 1,
            'new_count' => 1,
        ]);

        $product = TelegramChannelProduct::create([
            'telegram_scan_id' => $scan->id,
            'first_telegram_scan_id' => $scan->id,
            'channel' => 'asprinntrendy',
            'message_id' => 48320,
            'name' => 'Saten Gomlek',
            'price' => 7.5,
            'currency' => 'USD',
            'sizes' => ['S' => 1, 'M' => 1, 'L' => 1, 'XL' => 1],
            'status' => 'new',
        ]);

        $product->images()->createMany([
            ['type' => 'photo', 'source_url' => 'https://cdn4.telesco.pe/file/a', 'album_index' => 0, 'sort_order' => 0],
            // Ayni adres: tekrar eden gorsel, elenmeli.
            ['type' => 'photo', 'source_url' => 'https://cdn4.telesco.pe/file/a', 'album_index' => 1, 'sort_order' => 0],
            ['type' => 'photo', 'source_url' => 'https://cdn4.telesco.pe/file/b', 'album_index' => 1, 'sort_order' => 1],
        ]);

        return $product->fresh('images');
    }

    public function test_tekrar_eden_gorseller_elenir(): void
    {
        $candidate = $this->candidate();

        $page = new TelegramScanDetail;

        // Uc medya kaydi var ama ikisi ayni adres: iki tekil gorsel kalmali.
        $this->assertCount(3, $candidate->images);
        $this->assertCount(2, $page->uniqueImages($candidate));
    }

    public function test_beden_serisi_sistemdeki_bedenlere_baglanir(): void
    {
        $candidate = $this->candidate();

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->assertSet('quickAddId', $candidate->getKey())
            ->tap(function ($component) {
                $pack = $component->get('form.pack');

                $sMi = Size::query()->where('name', 'S')->value('id');
                $xsId = Size::query()->where('name', 'XS')->value('id');

                // Telegram'daki "1s" sistemdeki S bedenine dustu.
                $this->assertSame(1, $pack[$sMi]);
                // Seride olmayan beden sifir.
                $this->assertSame(0, $pack[$xsId]);
            });
    }

    public function test_urun_kataloga_aktarilir(): void
    {
        Storage::fake('public_media_products');

        $candidate = $this->candidate();
        $color = Color::query()->firstOrFail();
        $sizeS = Size::query()->where('name', 'S')->value('id');
        $sizeM = Size::query()->where('name', 'M')->value('id');

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->set('form.name', 'Saten Gomlek')
            ->set('form.price_usd', '7.5')
            ->set('form.category_id', Category::query()->value('id'))
            ->set('form.pack', [$sizeS => 1, $sizeM => 2])
            ->call('toggleColor', $color->getKey())
            ->call('saveQuickAdd')
            ->assertSet('quickAddId', null);

        // Test veritabaninda tohumlanmis urunler de var; aday kaydin
        // isaretledigi urune bakiyoruz.
        $candidate->refresh();
        $product = Product::query()->findOrFail($candidate->product_id);
        $this->assertSame('Saten Gomlek', $product->name['tr']);
        $this->assertSame('7.50', (string) $product->price_usd);
        $this->assertSame(3, $product->pack_size);

        // Iki beden x bir renk = iki varyant.
        $this->assertSame(2, $product->variants()->count());

        // Tekil iki gorsel indirildi, ilki kapak oldu.
        $this->assertSame(2, $product->images()->count());
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());

        // Aday kayit isaretlendi.
        $this->assertSame('imported', $candidate->status);
        $this->assertSame($product->getKey(), $candidate->product_id);
    }

    public function test_eksik_alanla_kaydedilmez(): void
    {
        $candidate = $this->candidate();

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            // Kategori ve renk secilmedi.
            ->call('saveQuickAdd')
            // Pencere acik kaldi, urun olusmadi.
            ->assertSet('quickAddId', $candidate->getKey());

        $candidate->refresh();

        $this->assertSame('new', $candidate->status);
        $this->assertNull($candidate->product_id);
    }

    public function test_telegram_kodu_kataloga_tasinmaz(): void
    {
        $candidate = $this->candidate();
        $candidate->update(['product_code' => '8017']);

        $color = Color::query()->firstOrFail();
        $sizeS = Size::query()->where('name', 'S')->value('id');

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            // Tedarikci kodu forma yazilmaz.
            ->assertSet('form.code', '')
            ->set('form.name', 'Kodsuz Urun')
            ->set('form.price_usd', '9')
            ->set('form.category_id', Category::query()->value('id'))
            ->set('form.pack', [$sizeS => 1])
            ->call('toggleColor', $color->getKey())
            ->call('saveQuickAdd');

        $candidate->refresh();
        $product = Product::query()->findOrFail($candidate->product_id);

        // Katalog kodu sistemin sirasindan geldi, tedarikcininki degil.
        $this->assertNotSame('8017', (string) $product->code);
        $this->assertNotEmpty($product->code);
        // Telegram kaydinda referans olarak duruyor.
        $this->assertSame('8017', $candidate->product_code);
    }

    public function test_kategori_kutudan_secilir_ve_geri_alinir(): void
    {
        $candidate = $this->candidate();
        $categoryId = Category::query()->value('id');

        $page = Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->assertSet('form.category_id', null);

        $page->call('selectCategory', $categoryId)
            ->assertSet('form.category_id', $categoryId);

        // Ayni kutuya tekrar basmak secimi kaldirir.
        $page->call('selectCategory', $categoryId)
            ->assertSet('form.category_id', null);
    }

    public function test_adi_olmayan_urune_gorselden_ad_uretilir(): void
    {
        config(['storefront.translation.api_key' => 'test-key']);

        $candidate = $this->candidate();
        $candidate->update(['name' => null, 'name_source' => null]);

        Http::fake([
            'cdn*.telesco.pe/*' => Http::response($this->jpeg(), 200, ['Content-Type' => 'image/jpeg']),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{"name":"Saten Kruvaze Elbise"}']]],
                ]],
            ]),
        ]);

        $sizeS = Size::query()->where('name', 'S')->value('id');

        $page = Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->assertSet('form.name', '')
            ->call('generateName')
            ->assertSet('form.name', 'Saten Kruvaze Elbise')
            ->assertSet('form.name_source', 'ai');

        // Kaydedilince aday kayda da islenir.
        $page->set('form.price_usd', '9')
            ->set('form.category_id', Category::query()->value('id'))
            ->set('form.pack', [$sizeS => 1])
            ->call('toggleColor', Color::query()->value('id'))
            ->call('saveQuickAdd');

        $candidate->refresh();

        $this->assertSame('Saten Kruvaze Elbise', $candidate->name);
        $this->assertSame('ai', $candidate->name_source);

        $product = Product::query()->findOrFail($candidate->product_id);
        $this->assertSame('Saten Kruvaze Elbise', $product->name['tr']);
    }

    public function test_anahtar_yoksa_ad_uretme_dugmesi_cikmaz(): void
    {
        config(['storefront.translation.api_key' => null]);

        $candidate = $this->candidate();

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->assertDontSee('Yeniden üret');
    }

    public function test_adsiz_urunde_ad_pencere_acilinca_kendiliginden_uretilir(): void
    {
        config(['storefront.translation.api_key' => 'test-key']);

        $candidate = $this->candidate();
        $candidate->update(['name' => null, 'name_source' => null]);

        Http::fake([
            'cdn*.telesco.pe/*' => Http::response($this->jpeg(), 200, ['Content-Type' => 'image/jpeg']),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => '{"name":"Keten Kruvaze Elbise"}']]]]],
            ]),
        ]);

        // Pencere adi bos acilir; panel gelen oneriyle bos alani doldurur.
        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->assertSet('form.name', '')
            ->call('applySuggestedName', 'Keten Kruvaze Elbise')
            ->assertSet('form.name', 'Keten Kruvaze Elbise')
            ->assertSet('form.name_source', 'ai');

        // Uretim ayri bilesende kosar: gorselden ad uretip aday kayda yazar ve
        // olayi panele gonderir. (Panel kilitlenmesin diye ayri bilesen.)
        Livewire::test(TelegramNameSuggester::class, ['productId' => $candidate->getKey()])
            ->call('suggest')
            ->assertDispatched('telegram-name-suggested', name: 'Keten Kruvaze Elbise');

        // Aday kayda hemen yazildi: ikinci acilista tekrar uretilmez.
        $candidate->refresh();
        $this->assertSame('Keten Kruvaze Elbise', $candidate->name);
        $this->assertSame('ai', $candidate->name_source);
    }

    public function test_ad_uretimi_basarisizsa_hata_modalda_gosterilir_ve_uretiliyor_takilmaz(): void
    {
        config(['storefront.translation.api_key' => 'test-key']);

        $candidate = $this->candidate();
        $candidate->update(['name' => null, 'name_source' => null]);

        Http::fake([
            'cdn*.telesco.pe/*' => Http::response($this->jpeg(), 200, ['Content-Type' => 'image/jpeg']),
            // Gemini hata döndürür: ad üretilemez.
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'kota doldu']], 429),
        ]);

        // Pencere adsiz acilir: uretim beklemede, yer tutucu "üretiliyor".
        $panel = Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->assertSet('form.name_pending', true);

        // Ayri bilesen basarisiz olur ve panele hata olayi gonderir.
        Livewire::test(TelegramNameSuggester::class, ['productId' => $candidate->getKey()])
            ->call('suggest')
            ->assertDispatched('telegram-name-failed');

        // Panel olayi alinca: beklemeyi kapatir (yer tutucu takilmaz) ve hatayi
        // modal ici bildirimde gosterir.
        $panel->call('applyNameFailure', 'kota doldu')
            ->assertSet('form.name_pending', false)
            ->assertSet('quickAddFlash.type', 'danger');
    }

    public function test_adi_olan_urunde_otomatik_uretim_calismaz(): void
    {
        config(['storefront.translation.api_key' => 'test-key']);

        $candidate = $this->candidate();

        // Adi kanaldan gelen urunde panel, gecikmis bir oneri gelse bile mevcut
        // adi ezmez.
        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->call('applySuggestedName', 'Baska Ad')
            ->assertSet('form.name', 'Saten Gomlek');
    }

    public function test_secili_gorseller_sira_numarasiyla_isaretlenir(): void
    {
        $candidate = $this->candidate();

        $page = Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey());

        $tekil = (new TelegramScanDetail)->uniqueImages($candidate);
        $ilk = $tekil->first()->getKey();
        $ikinci = $tekil->last()->getKey();

        // Hepsi secili acilir: sira 1 ve 2, kapak ilki.
        $page->tap(function ($c) use ($candidate, $ilk, $ikinci) {
            $detay = $c->instance();
            $this->assertSame([$ilk => 1, $ikinci => 2], $detay->selectionOrder($candidate));
            $this->assertSame($ilk, $detay->coverImageId($candidate));
        });

        // Ilkini cikarinca ikinci hem 1. sıraya hem kapaga gecer.
        $page->call('toggleImage', $ilk)
            ->tap(function ($c) use ($candidate, $ikinci) {
                $detay = $c->instance();
                $this->assertSame([$ikinci => 1], $detay->selectionOrder($candidate));
                $this->assertSame($ikinci, $detay->coverImageId($candidate));
            });

        // Eksik secimde "hepsini sec" tamamlar, tam secimde temizler.
        $page->call('toggleAllImages')
            ->tap(fn ($c) => $this->assertCount(2, $c->get('form.image_ids')));

        $page->call('toggleAllImages')->assertSet('form.image_ids', []);
    }

    public function test_gorseller_surukle_birakla_yeniden_siralanir(): void
    {
        $candidate = $this->candidate();

        $page = Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey());

        $tekil = (new TelegramScanDetail)->uniqueImages($candidate);
        $ilk = $tekil->first()->getKey();
        $ikinci = $tekil->last()->getKey();

        // Sürükleyip sırayı ters çevir: numara ve kapak yeni sırayı izler.
        $page->call('reorderImages', [$ikinci, $ilk])
            ->tap(function ($c) use ($candidate, $ilk, $ikinci) {
                $detay = $c->instance();
                $this->assertSame([$ikinci => 1, $ilk => 2], $detay->selectionOrder($candidate));
                $this->assertSame($ikinci, $detay->coverImageId($candidate));
            });

        // Kaydedince katalog görselleri de bu sırayla yazılır: ters çevrilen
        // sırada önce gelen fotoğraf kapak (sort_order 0, is_primary) olur.
        Storage::fake('public_media_products');
        $sizeS = Size::query()->where('name', 'S')->value('id');

        $page->set('form.category_id', Category::query()->value('id'))
            ->set('form.price_usd', '9.9')
            ->set('form.pack', [$sizeS => 1])
            ->call('toggleColor', Color::query()->firstOrFail()->getKey())
            ->call('saveQuickAdd')
            ->assertSet('quickAddId', null);

        $candidate->refresh();
        $urun = Product::query()->findOrFail($candidate->product_id);
        $ilkGorsel = $urun->images()->orderBy('sort_order')->first();

        $this->assertTrue((bool) $ilkGorsel->is_primary);
        $this->assertSame(0, $ilkGorsel->sort_order);
    }

    public function test_sistemde_olmayan_renk_eklenip_secilir(): void
    {
        // Ceviri servisi kapali: renk yine de eklenmeli.
        config(['storefront.translation.api_key' => null]);

        $candidate = $this->candidate();
        $onceki = Color::query()->count();

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->set('newColorName', 'Adacayi Yesili')
            ->set('newColorHex', '#7FB069')
            ->call('addColor')
            // Kutu temizlendi.
            ->assertSet('newColorName', '')
            // Geri bildirim modal icinde gosterilir (sayfa toast'i degil).
            ->assertSet('quickAddFlash.type', 'success')
            ->tap(function ($c) use ($onceki) {
                $this->assertSame($onceki + 1, Color::query()->count());

                $renk = Color::query()->where('name', 'Adacayi Yesili')->firstOrFail();

                $this->assertSame('#7FB069', $renk->hex);
                $this->assertTrue($renk->active);
                $this->assertSame('Adacayi Yesili', $renk->name_i18n['tr']);

                // Eklenen renk dogrudan secili geldi.
                $this->assertContains($renk->getKey(), $c->get('form.color_ids'));
            });
    }

    public function test_bos_isimle_renk_eklenmez_ve_modal_ici_uyari_cikar(): void
    {
        $candidate = $this->candidate();
        $onceki = Color::query()->count();

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->set('newColorName', '   ')
            ->call('addColor')
            // Renk olusmaz ama sessiz kalmaz: modal icinde uyari gosterilir.
            ->assertSet('quickAddFlash.type', 'warning')
            ->assertSet('quickAddFlash.message', 'Renk adı girin');

        $this->assertSame($onceki, Color::query()->count());
    }

    public function test_ayni_isimli_renk_ikinci_kez_olusmaz(): void
    {
        config(['storefront.translation.api_key' => null]);

        $candidate = $this->candidate();
        $mevcut = Color::query()->firstOrFail();
        $onceki = Color::query()->count();

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            // Buyuk/kucuk harf farkiyla yazilsa bile ayni renk.
            ->set('newColorName', mb_strtoupper($mevcut->name))
            ->call('addColor')
            ->tap(function ($c) use ($onceki, $mevcut) {
                $this->assertSame($onceki, Color::query()->count());
                $this->assertContains($mevcut->getKey(), $c->get('form.color_ids'));
            });
    }

    public function test_kanaldan_gelen_renk_sistemde_varsa_secili_gelir(): void
    {
        $candidate = $this->candidate();
        $mevcut = Color::query()->firstOrFail();

        // Kanal iki renk yazmis: biri sistemde var, biri yok, biri de cop.
        $candidate->update(['colors' => [$mevcut->name, 'Haki', 'yeni']]);

        $page = Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey());

        // Sistemdeki renk onden secili.
        $this->assertContains($mevcut->getKey(), $page->get('form.color_ids'));

        $detay = $page->instance();
        $adlar = array_column($detay->channelColors($candidate->fresh()), 'name');

        // "yeni" renk sayilmaz, elendi.
        $this->assertContains('Haki', $adlar);
        $this->assertNotContains('yeni', $adlar);
    }

    public function test_kanaldaki_renk_tek_tikla_eklenir(): void
    {
        config(['storefront.translation.api_key' => null]);

        $candidate = $this->candidate();
        $candidate->update(['colors' => ['Haki']]);

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->call('addChannelColor', 'Haki')
            ->tap(function ($c) {
                $renk = Color::query()->where('name', 'Haki')->firstOrFail();

                // Bilinen ton icin tahmini renk kodu atandi, siyah kalmadi.
                $this->assertSame('#78866B', $renk->hex);
                $this->assertContains($renk->getKey(), $c->get('form.color_ids'));
            });
    }

    public function test_ana_sayfada_goster_secimi_urune_islenir(): void
    {
        $candidate = $this->candidate();
        $sizeS = Size::query()->where('name', 'S')->value('id');

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            // Varsayilan acik.
            ->assertSet('form.show_on_home', true)
            ->call('toggleShowOnHome')
            ->assertSet('form.show_on_home', false)
            ->set('form.name', 'Vitrinsiz Urun')
            ->set('form.price_usd', '9')
            ->set('form.category_id', Category::query()->value('id'))
            ->set('form.pack', [$sizeS => 1])
            ->call('toggleColor', Color::query()->value('id'))
            ->call('saveQuickAdd');

        $candidate->refresh();
        $product = Product::query()->findOrFail($candidate->product_id);

        $this->assertFalse($product->show_on_home);
        // Yayindan kaldirilmadi, yalnizca ana sayfada cikmiyor.
        $this->assertTrue($product->active);
    }

    public function test_secilen_video_indirilip_urune_baglanir(): void
    {
        Storage::fake('public_media_products');

        $candidate = $this->candidate();

        $candidate->images()->createMany([
            [
                'type' => 'video',
                'source_url' => 'https://cdn4.telesco.pe/file/v1.mp4',
                'poster_url' => 'https://cdn4.telesco.pe/file/kapak1',
                'duration' => '0:19',
                'downloadable' => true,
                'album_index' => 2,
                'sort_order' => 0,
            ],
            [
                // Telegram dosyayi vermiyor: atlanmali.
                'type' => 'video',
                'source_url' => null,
                'poster_url' => 'https://cdn4.telesco.pe/file/kapak2',
                'downloadable' => false,
                'album_index' => 2,
                'sort_order' => 1,
            ],
        ]);

        $candidate = $candidate->fresh('images');
        $sizeS = Size::query()->where('name', 'S')->value('id');

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->set('form.name', 'Videolu Urun')
            ->set('form.price_usd', '12')
            ->set('form.category_id', Category::query()->value('id'))
            ->set('form.pack', [$sizeS => 1])
            ->call('toggleColor', Color::query()->value('id'))
            ->call('saveQuickAdd');

        $candidate->refresh();
        $product = Product::query()->findOrFail($candidate->product_id);

        // Video indirildi, urune baglandi ve dosya diske yazildi.
        $this->assertNotEmpty($product->video_url);
        $this->assertStringEndsWith('.mp4', $product->video_url);
        Storage::disk('public_media_products')->assertExists($product->video_url);
    }

    public function test_indirilemeyen_video_urune_baglanmaz(): void
    {
        Storage::fake('public_media_products');

        $candidate = $this->candidate();

        $candidate->images()->create([
            'type' => 'video',
            'source_url' => null,
            'poster_url' => 'https://cdn4.telesco.pe/file/kapak',
            'downloadable' => false,
            'album_index' => 2,
            'sort_order' => 0,
        ]);

        $candidate = $candidate->fresh('images');
        $sizeS = Size::query()->where('name', 'S')->value('id');

        Livewire::test(TelegramScanDetail::class, ['record' => $candidate->telegram_scan_id])
            ->call('openQuickAdd', $candidate->getKey())
            ->set('form.name', 'Videosuz Urun')
            ->set('form.price_usd', '12')
            ->set('form.category_id', Category::query()->value('id'))
            ->set('form.pack', [$sizeS => 1])
            ->call('toggleColor', Color::query()->value('id'))
            ->call('saveQuickAdd');

        $candidate->refresh();
        $product = Product::query()->findOrFail($candidate->product_id);

        $this->assertEmpty($product->video_url);
        // Fotograflar yine de aktarildi.
        $this->assertGreaterThan(0, $product->images()->count());
    }
}
