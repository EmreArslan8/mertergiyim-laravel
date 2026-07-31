<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\MediaPost;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\UploadTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageFolderTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_files_are_grouped_into_their_album_folder(): void
    {
        $disk = Storage::disk(UploadTarget::disk('site'));
        $disk->put(UploadTarget::pathPrefix('site').'media/yeni-dosya.webp', 'x');

        $album = MediaPost::create(['title' => ['tr' => 'Vitrin'], 'active' => true, 'sort_order' => 0]);

        $file = MediaFile::create([
            'media_post_id' => $album->getKey(),
            'type' => 'image',
            'file_path' => 'media/yeni-dosya.webp',
        ]);

        $expected = 'media/'.$album->getKey().'/yeni-dosya.webp';

        $this->assertSame($expected, $file->fresh()->file_path);
        $this->assertTrue($disk->exists(UploadTarget::pathPrefix('site').$expected));
        $this->assertFalse($disk->exists(UploadTarget::pathPrefix('site').'media/yeni-dosya.webp'));
    }

    public function test_product_images_are_grouped_into_their_product_folder(): void
    {
        $disk = Storage::disk(UploadTarget::disk('products'));
        $disk->put(UploadTarget::pathPrefix('products').'kok-gorsel.webp', 'x');

        $product = Product::create([
            'code' => 'TST-1',
            'slug' => 'test-urun',
            'name' => ['tr' => 'Test Ürün'],
            'description' => ['tr' => 'Test açıklama'],
            'price' => 1200,
            'stock_status' => 'in_stock',
            'active' => true,
        ]);

        $image = ProductImage::create([
            'product_id' => $product->getKey(),
            'storage_path' => 'kok-gorsel.webp',
            'alt' => ['tr' => 'Test görsel'],
            'sort_order' => 0,
        ]);

        $expected = $product->getKey().'/kok-gorsel.webp';

        $this->assertSame($expected, $image->fresh()->storage_path);
        $this->assertTrue($disk->exists(UploadTarget::pathPrefix('products').$expected));
    }

    public function test_paths_already_in_the_right_folder_and_missing_files_are_left_alone(): void
    {
        $album = MediaPost::create(['title' => ['tr' => 'Vitrin'], 'active' => true, 'sort_order' => 0]);

        // Zaten albüm klasöründe: dokunulmaz.
        $disk = Storage::disk(UploadTarget::disk('site'));
        $inPlace = 'media/'.$album->getKey().'/duran.webp';
        $disk->put(UploadTarget::pathPrefix('site').$inPlace, 'x');

        $kept = MediaFile::create([
            'media_post_id' => $album->getKey(),
            'type' => 'image',
            'file_path' => $inPlace,
        ]);

        $this->assertSame($inPlace, $kept->fresh()->file_path);

        // Yerel diskte olmayan eski Supabase kaydı: yol korunur, hata vermez.
        $legacy = MediaFile::create([
            'media_post_id' => $album->getKey(),
            'type' => 'image',
            'file_path' => 'media/supabasede-duran.webp',
        ]);

        $this->assertSame('media/supabasede-duran.webp', $legacy->fresh()->file_path);
    }
}
