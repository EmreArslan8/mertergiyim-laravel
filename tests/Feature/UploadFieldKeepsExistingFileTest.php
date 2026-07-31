<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\ImageUploader;
use App\Support\UploadTarget;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Panelin yükleme alanı, düzenlemeye girildiğinde kayıtlı dosyayı korumalı.
 *
 * Filament dosyanın varlığını `disk->exists($veritabanindakiYol)` ile
 * doğruluyor ve bulamazsa alanı sessizce boşaltıyor. Yerel diskte dosyalar
 * bucket klasörüne (product-images/) yazılıyor ama veritabanında yol öneksiz
 * (xxx.webp) tutuluyordu; ikisi eşleşmediği için düzenleme ekranında görsel
 * kayboluyor, kaydedince de siliniyordu. Supabase kurulumunda önek boş olduğu
 * için hata yalnızca alwaysdata'da görünüyordu.
 */
class UploadFieldKeepsExistingFileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    public function test_uploaded_path_is_reachable_on_the_configured_disk(): void
    {
        $path = app(ImageUploader::class)->store(
            UploadedFile::fake()->image('kapak.jpg', 40, 40),
            'products',
        );

        // Kaydedilen yol, alanın baktığı diskte birebir bulunabilmeli.
        $this->assertTrue(
            Storage::disk(UploadTarget::disk('products'))->exists($path),
            'Yüklenen dosya, panelin baktığı diskte veritabanındaki yolla bulunamıyor.',
        );
    }

    public function test_edit_page_keeps_the_existing_product_image(): void
    {
        $product = Product::query()->firstOrFail();
        $product->images()->delete();

        $path = app(ImageUploader::class)->store(
            UploadedFile::fake()->image('kapak.jpg', 40, 40),
            'products',
        );

        $image = ProductImage::query()->create([
            'product_id' => $product->getKey(),
            'storage_path' => $path,
            'alt' => ['tr' => 'Kapak'],
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $state = Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->assertOk()
            ->get('data');

        $images = $state['images'] ?? [];
        $first = is_array($images) ? reset($images) : [];

        $this->assertNotEmpty(
            $first['storage_path'] ?? null,
            'Düzenleme ekranı kayıtlı görseli boşalttı.',
        );

        // Kaydetmek dosyayı düşürmemeli: alan boş gelirse kayıt da siliniyordu.
        $this->assertTrue(Storage::disk(UploadTarget::disk('products'))->exists($image->refresh()->storage_path));
    }
}
