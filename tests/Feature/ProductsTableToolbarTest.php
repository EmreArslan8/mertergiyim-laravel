<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ürün listesinin üst şeridi: arama + kategori/yayın filtreleri + sıralama.
 */
class ProductsTableToolbarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    public function test_filters_and_sort_are_visible_above_the_table(): void
    {
        Livewire::test(ListProducts::class)
            ->assertOk()
            ->assertSee('Kategori')
            ->assertSee('Yayın durumu')
            ->assertSee('Sıralama')
            // Buton etiketi tek kelime; "Sıralama" filtresiyle karışmasın diye
            // butonun kendi Livewire eylemi aranıyor.
            ->assertSee('toggleTableReordering', escape: false);
    }

    public function test_search_works_on_the_current_database_driver(): void
    {
        // Arama daha önce Postgres'e özgü `ilike` kullanıyordu ve MariaDB'de
        // SQL sözdizimi hatası veriyordu.
        $records = Livewire::test(ListProducts::class)
            ->set('tableSearch', 'zimme')
            ->instance()
            ->getTableRecords();

        $this->assertNotNull($records);
    }

    public function test_sort_selection_changes_record_order(): void
    {
        $codes = fn (string $value): array => Livewire::test(ListProducts::class)
            ->set('tableFilters.sort.value', $value)
            ->instance()
            ->getTableRecords()
            ->pluck('code')
            ->take(3)
            ->all();

        $byCode = $codes('code');
        $newest = $codes('newest');
        $oldest = $codes('oldest');

        $sorted = $byCode;
        sort($sorted);

        $this->assertSame($sorted, $byCode, 'Ürün kodu A-Z sıralaması uygulanmadı.');
        $this->assertNotSame($newest, $oldest, 'En yeni ve en eski aynı sırayı verdi.');

        // Seed verisinde ürün kodu ekleme sırasıyla artıyor: en eski = küçük kod.
        $this->assertLessThan($newest[0], $oldest[0], 'En eski seçimi en yeniyi getirdi.');
    }

    public function test_reordering_products_updates_storefront_order_and_clears_home_cache(): void
    {
        $ids = Product::query()->orderBy('sort_order')->pluck('id')->all();
        $reversed = array_reverse($ids);
        Cache::put('storefront:home', ['stale' => true], 600);

        Livewire::test(ListProducts::class)
            ->call('reorderTable', $reversed);

        $this->assertFalse(Cache::has('storefront:home'));
        $this->assertSame(
            $reversed,
            Product::query()->orderBy('sort_order')->pluck('id')->all(),
        );
    }
}
