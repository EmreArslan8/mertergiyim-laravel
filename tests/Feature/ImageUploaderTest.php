<?php

namespace Tests\Feature;

use App\Services\ImageUploader;
use App\Support\Storefront;
use App\Support\UploadTarget;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploaderTest extends TestCase
{
    public function test_it_resizes_and_stores_under_the_bucket_path(): void
    {
        $uploader = app(ImageUploader::class);
        $file = UploadedFile::fake()->image('Büyük Ürün.jpg', 3000, 2000);

        $path = $uploader->store($file, 'products', 'test-product');

        // storage_path bucket'a göreli kalmalı (vitrin publicStorageUrl ile uyumlu).
        $this->assertStringStartsWith('test-product/', $path);
        $this->assertStringEndsWith('.webp', $path);

        $disk = Storage::disk(UploadTarget::disk('products'));
        $stored = UploadTarget::pathPrefix('products').$path;
        $this->assertTrue($disk->exists($stored));

        $image = imagecreatefromstring($disk->get($stored));
        $this->assertLessThanOrEqual((int) config('storefront.upload.max_size'), imagesx($image));

        $this->assertStringContainsString($path, Storefront::storageUrl('products', $path));

        $uploader->delete('products', $path);
        $this->assertFalse($disk->exists($stored));
    }
}
