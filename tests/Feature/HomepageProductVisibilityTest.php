<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\StorefrontRepository;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Ana sayfa vitrini: hangi ürünlerin çıkacağı panelden seçiliyor.
 */
class HomepageProductVisibilityTest extends TestCase
{
    public function test_ana_sayfada_gosterilmeyen_urun_vitrinde_cikmaz(): void
    {
        $gizli = Product::query()->active()->firstOrFail();
        $gizli->forceFill(['show_on_home' => false])->save();

        Cache::flush();

        $urunler = collect(app(StorefrontRepository::class)->home()['products'])->pluck('id');

        $this->assertNotContains($gizli->getKey(), $urunler);
    }

    public function test_varsayilan_olarak_urunler_ana_sayfada_gorunur(): void
    {
        $urun = Product::query()->active()->firstOrFail();

        $this->assertTrue($urun->show_on_home);

        Cache::flush();

        $urunler = collect(app(StorefrontRepository::class)->home()['products'])->pluck('id');

        $this->assertContains($urun->getKey(), $urunler);
    }
}
