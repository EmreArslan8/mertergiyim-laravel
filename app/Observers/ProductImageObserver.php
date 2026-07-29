<?php

namespace App\Observers;

use App\Models\ProductImage;
use App\Services\ImageUploader;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ProductImageObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(ProductImage $image): void
    {
        if (! $image->wasChanged('storage_path')) {
            return;
        }

        app(ImageUploader::class)->delete('products', $image->getOriginal('storage_path'));
    }

    public function deleted(ProductImage $image): void
    {
        app(ImageUploader::class)->delete('products', $image->storage_path);
    }
}
