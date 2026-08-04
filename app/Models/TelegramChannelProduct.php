<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Telegram kanalından çekilmiş, henüz kataloğa girmemiş ürün adayı.
 */
class TelegramChannelProduct extends Model
{
    use HasUuidKey;

    /** Kaynak kanallar; scraper ve panel filtresi aynı listeyi kullanır. */
    public const CHANNELS = [
        'asprinntrendy' => 'AsprinTrendy',
        'naturallover' => 'Natural Love',
        'rosearyaa' => 'RoseArya',
    ];

    public const STATUSES = [
        'new' => 'Bekliyor',
        'enriched' => 'Zenginleştirildi',
        'approved' => 'Onaylandı',
        'imported' => 'Kataloğa aktarıldı',
        'ignored' => 'Elendi',
    ];

    protected $guarded = [];

    protected $casts = [
        'sizes' => 'array',
        'colors' => 'array',
        'price' => 'decimal:2',
        'pack_size' => 'integer',
        'message_id' => 'integer',
        'posted_at' => 'datetime',
        'scraped_at' => 'datetime',
        'source_changed_at' => 'datetime',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(TelegramChannelProductImage::class)
            ->orderBy('album_index')
            ->orderBy('sort_order');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function channelLabel(): string
    {
        return self::CHANNELS[$this->channel] ?? $this->channel;
    }

    /** Listede gösterilecek ilk görsel; henüz indirilmediyse CDN adresi. */
    public function coverUrl(): ?string
    {
        $image = $this->relationLoaded('images')
            ? $this->images->first()
            : $this->images()->first();

        return $image?->url();
    }
}
