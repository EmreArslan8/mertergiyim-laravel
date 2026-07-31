<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use App\Support\StorageFolder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        'alt' => 'array',
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // Görseller ürün klasöründe toplanır: product-images/<ürün_id>/
        // Ürün formundan yüklerken id henüz yok, taşıma kayıttan sonra olur.
        static::saved(function (self $image): void {
            StorageFolder::relocate($image, 'storage_path', 'products', '', $image->product_id);

            // Kapak görsel tek olabilir. Panelde iki kayıt da is_primary=true
            // kalabiliyordu; vitrin o durumda sort_order'a düşüp beklenmeyen
            // görseli kapak yapıyordu.
            if ($image->is_primary && $image->product_id) {
                static::query()
                    ->where('product_id', $image->product_id)
                    ->whereKeyNot($image->getKey())
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            static::ensurePrimaryExists($image->product_id);
        });

        // Kapak silinirse ürün kapaksız kalmasın.
        static::deleted(function (self $image): void {
            static::ensurePrimaryExists($image->product_id);
        });
    }

    /**
     * Ürünün en az bir kapak görseli olmalı; yoksa sıradaki ilk görsel kapak olur.
     */
    protected static function ensurePrimaryExists(?string $productId): void
    {
        if (! $productId) {
            return;
        }

        $images = static::query()->where('product_id', $productId);

        if ((clone $images)->where('is_primary', true)->exists()) {
            return;
        }

        $first = (clone $images)->orderBy('sort_order')->orderBy('id')->first();

        // Olay döngüsüne girmemek için doğrudan sorgu ile güncellenir.
        $first?->newQuery()->whereKey($first->getKey())->update(['is_primary' => true]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
