<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use App\Support\Storefront;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramChannelProductImage extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        'message_id' => 'integer',
        'album_index' => 'integer',
        'sort_order' => 'integer',
    ];

    public function telegramChannelProduct(): BelongsTo
    {
        return $this->belongsTo(TelegramChannelProduct::class);
    }

    /**
     * İndirilmişse depodaki dosya, değilse Telegram CDN adresi.
     * CDN bağlantıları kalıcı değil; kataloğa aktarmadan önce indirilmeli.
     *
     * 20 MB üstü videolarda Telegram dosya adresi vermiyor (downloadable =
     * false); o kayıtlarda elde yalnızca kapak karesi kalıyor ve burası null
     * döner.
     */
    public function url(): ?string
    {
        return $this->file_path
            ? Storefront::storageUrl('telegram', $this->file_path)
            : $this->source_url;
    }

    /** Listede ve galeride gösterilecek kare: videoda kapak, fotoğrafta kendisi. */
    public function thumbnailUrl(): ?string
    {
        return $this->type === 'video'
            ? ($this->poster_url ?: $this->url())
            : $this->url();
    }
}
