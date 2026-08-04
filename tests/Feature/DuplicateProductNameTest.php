<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\TranslateService;
use App\Support\ProductName;
use Illuminate\Database\UniqueConstraintViolationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Aynı ürünün ikinci kez girilmesi engellenir: kimlik ürün kodudur, ad ise
 * mükerrer kaydı önleyen benzersiz anahtardır (products.name_key).
 */
class DuplicateProductNameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
        $this->mock(TranslateService::class, fn ($mock) => $mock->shouldReceive('translateFields')->andReturn([]));
    }

    public function test_the_same_name_cannot_be_entered_twice(): void
    {
        $existing = $this->product('Beyaz Atlet', 'MG-1001');

        Livewire::test(CreateProduct::class)
            ->fillForm($this->formData('Beyaz Atlet'))
            ->call('create')
            ->assertHasFormErrors(['name.tr'])
            ->assertSeeHtml('class="merter-duplicate-card"')
            ->assertSee('Bu ürün zaten kayıtlı')
            ->assertSeeHtml('target="_blank"')
            ->assertSee('Mevcut ürünü aç');

        $this->assertSame(1, Product::query()->where('name_key', 'beyaz-atlet')->count());
        $this->assertSame('MG-1001', $existing->fresh()->code);
    }

    /**
     * Yazım farkları da aynı ürüne iner: kontrol ham metne değil anahtara bakar.
     */
    public function test_spelling_variants_are_treated_as_the_same_name(): void
    {
        $this->product('Beyaz Atlet', 'MG-1001');

        foreach (['BEYAZ ATLET', 'beyaz  atlet', 'Beyaz Atlet.', 'beyaz atlet '] as $variant) {
            Livewire::test(CreateProduct::class)
                ->fillForm($this->formData($variant))
                ->call('create')
                ->assertHasFormErrors(['name.tr']);
        }

        $this->assertSame(1, Product::query()->where('name_key', 'beyaz-atlet')->count());
    }

    /**
     * Yayından kaldırılmış ürünün adı da rezervedir; aksi hâlde ikizi oluşurdu.
     */
    public function test_an_unpublished_product_still_reserves_its_name(): void
    {
        $this->product('Beyaz Atlet', 'MG-1001', active: false);

        Livewire::test(CreateProduct::class)
            ->fillForm($this->formData('Beyaz Atlet'))
            ->call('create')
            ->assertHasFormErrors(['name.tr']);
    }

    public function test_a_distinguishable_name_is_accepted(): void
    {
        $this->product('Beyaz Atlet', 'MG-1001');

        Livewire::test(CreateProduct::class)
            ->fillForm($this->formData('Beyaz Atlet İp Askılı'))
            ->call('create')
            ->assertHasNoFormErrors(['name.tr']);

        // Kayıt yolu ayrıca doğrulanır: anahtar ve bağlantı addan üretilir.
        $product = $this->product('Beyaz Atlet İp Askılı');

        $this->assertSame('beyaz-atlet-ip-askili', $product->name_key);
        $this->assertSame('beyaz-atlet-ip-askili', $product->slug);
    }

    /**
     * Düzenlemede ürün kendini mükerrer saymamalı.
     */
    public function test_saving_a_product_without_renaming_it_is_allowed(): void
    {
        $product = $this->product('Beyaz Atlet', 'MG-1001');

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->fillForm(['name' => ['tr' => 'Beyaz Atlet']])
            ->call('save')
            ->assertHasNoFormErrors(['name.tr']);
    }

    public function test_a_product_cannot_be_renamed_onto_another_product(): void
    {
        $this->product('Beyaz Atlet', 'MG-1001');
        $other = $this->product('Krem Atlet');

        Livewire::test(EditProduct::class, ['record' => $other->getKey()])
            ->fillForm(['name' => ['tr' => 'Beyaz Atlet']])
            ->call('save')
            ->assertHasFormErrors(['name.tr']);

        $this->assertSame('krem-atlet', $other->fresh()->name_key);
    }

    /**
     * Ad değişince bağlantı da yenilenir, üründe kod izi kalmaz.
     */
    public function test_renaming_a_product_renews_its_link(): void
    {
        $product = $this->product('Beyaz Atlet', 'MG-1001');

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->fillForm(['name' => ['tr' => 'Krem Atlet']])
            ->call('save')
            ->assertHasNoFormErrors(['name.tr']);

        $product->update(['name' => ['tr' => 'Krem Atlet']]);
        $fresh = $product->fresh();

        $this->assertSame('krem-atlet', $fresh->slug);
        $this->assertSame('krem-atlet', $fresh->name_key);
    }

    /**
     * Silinen ürünün adı serbest kalır: silme kalıcıdır, yayından kaldırmaktan
     * farkı budur.
     */
    public function test_deleting_a_product_frees_its_name(): void
    {
        $this->product('Beyaz Atlet', 'MG-1001')->delete();

        Livewire::test(CreateProduct::class)
            ->fillForm($this->formData('Beyaz Atlet'))
            ->call('create')
            ->assertHasNoFormErrors(['name.tr']);

        $this->assertNull(ProductName::duplicate('Beyaz Atlet'));
    }

    /**
     * Panel atlanırsa (eşzamanlı kayıt, toplu içe aktarma) veritabanı reddeder.
     */
    public function test_the_database_rejects_a_duplicate_that_bypasses_the_panel(): void
    {
        $this->product('Beyaz Atlet', 'MG-1001');

        $this->expectException(UniqueConstraintViolationException::class);

        $this->product('BEYAZ ATLET');
    }

    public function test_similar_products_are_listed_without_blocking(): void
    {
        $this->product('Beyaz Atlet', 'MG-1001');

        $similar = ProductName::similar('Beyaz Atlet Bayan');

        $this->assertCount(1, $similar);
        $this->assertSame('MG-1001', $similar[0]->code);
        $this->assertNull(ProductName::duplicate('Beyaz Atlet Bayan'));
    }

    public function test_a_one_character_typo_suggests_the_closest_product_without_blocking(): void
    {
        $existing = $this->product('Ketenli', 'MG-1002');

        $suggestion = ProductName::closestTypo('Ktenli');

        $this->assertNotNull($suggestion);
        $this->assertTrue($existing->is($suggestion));
        $this->assertTrue($existing->is(ProductName::closestTypo('Keetnli')));
        $this->assertNull(ProductName::duplicate('Ktenli'));

        Livewire::test(CreateProduct::class)
            ->fillForm($this->formData('Ktenli'))
            ->assertSee('Bunu mu demek istediniz?')
            ->assertSee('Ketenli')
            ->assertSeeHtml('class="merter-duplicate-card merter-typo-card"')
            ->call('create')
            ->assertHasNoFormErrors(['name.tr']);
    }

    public function test_a_meaningfully_different_name_is_not_shown_as_a_typo(): void
    {
        $this->product('Ketenli', 'MG-1002');

        $this->assertNull(ProductName::closestTypo('Keten Elbise'));
        $this->assertNull(ProductName::closestTypo('Keten'));
    }

    /**
     * Kod panelde girilmez; sistem sırayla atar.
     */
    public function test_the_system_assigns_sequential_product_codes(): void
    {
        $first = $this->product('Beyaz Atlet', null);
        $second = $this->product('Krem Atlet', null);

        $this->assertSame($this->codeNumber($first) + 1, $this->codeNumber($second));
        $this->assertMatchesRegularExpression('/^MG-\d{4,}$/', $second->code);
    }

    public function test_a_deleted_product_code_is_never_assigned_again(): void
    {
        $deleted = $this->product('Silinecek Ürün', null);
        $deletedNumber = $this->codeNumber($deleted);
        $deleted->delete();

        $next = $this->product('Sonraki Ürün', null);

        $this->assertSame($deletedNumber + 1, $this->codeNumber($next));
        $this->assertNotSame($deleted->code, $next->code);
    }

    private function codeNumber(Product $product): int
    {
        return (int) preg_replace('/\D+/', '', (string) $product->code);
    }

    public function test_an_explicit_code_is_kept(): void
    {
        $this->assertSame('MG-1001', $this->product('Beyaz Atlet', 'MG-1001')->code);
    }

    private function product(string $name, ?string $code = null, bool $active = true): Product
    {
        return Product::create([
            'category_id' => $this->category()->getKey(),
            'code' => $code,
            'name' => ['tr' => $name],
            'description' => ['tr' => ''],
            'price_try' => 100,
            'stock_status' => true,
            'active' => $active,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(string $name): array
    {
        return [
            'category_id' => $this->category()->getKey(),
            'name' => ['tr' => $name],
            'price_try' => 100,
            'stock_status' => true,
            'active' => true,
        ];
    }

    private function category(): Category
    {
        return Category::query()->firstOrFail();
    }
}
