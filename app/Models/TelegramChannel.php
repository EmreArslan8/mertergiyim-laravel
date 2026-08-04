<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Taranacak Telegram kanalı. Panelden eklenip çıkarılır.
 */
class TelegramChannel extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
        'last_scanned_message_id' => 'integer',
        'last_scanned_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Kullanıcı "@naturallover" ya da tam adres yapıştırabiliyor; hepsi
        // sade kullanıcı adına indirgenir ki benzersizlik kısıtı tutsun.
        static::saving(function (self $channel): void {
            $channel->username = self::normalizeUsername($channel->username);
        });
    }

    public static function normalizeUsername(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('#^https?://t\.me/(s/)?#i', '', $value) ?? $value;

        return ltrim(trim($value, "/ \t\n\r"), '@');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function label(): string
    {
        return $this->title ?: '@'.$this->username;
    }

    public function url(): string
    {
        return 'https://t.me/'.$this->username;
    }
}
