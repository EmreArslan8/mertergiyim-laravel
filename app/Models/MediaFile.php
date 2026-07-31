<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use App\Support\StorageFolder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaFile extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        'alt' => 'array',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // Dosyalar albüm klasöründe toplanır: site-media/media/<albüm_id>/
        static::saved(function (self $file): void {
            StorageFolder::relocate($file, 'file_path', 'site', 'media', $file->media_post_id);
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(MediaPost::class, 'media_post_id');
    }
}
