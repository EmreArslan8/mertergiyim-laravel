<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Tek bir "ürün çek" işlemi. Panelde "#7" diye listelenir.
 */
class TelegramScan extends Model
{
    use HasUuidKey;

    public const STATUSES = [
        'queued' => 'Sırada',
        'running' => 'Çalışıyor',
        'completed' => 'Tamamlandı',
        'failed' => 'Başarısız',
    ];

    protected $guarded = [];

    protected $casts = [
        'channels' => 'array',
        'message_limit' => 'integer',
        'found_count' => 'integer',
        'new_count' => 'integer',
        'changed_count' => 'integer',
        'cursor' => 'integer',
        'imported_count' => 'integer',
        'number' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // uuid birincil anahtar olduğu için sıra numarasını burada veriyoruz.
        // Eşzamanlı iki tarama aynı numarayı almasın diye satırlar kilitlenir;
        // number sütunundaki benzersiz kısıt son güvence.
        static::creating(function (self $scan): void {
            $scan->number ??= DB::transaction(
                fn (): int => (int) static::query()->lockForUpdate()->max('number') + 1
            );
        });
    }

    /** Bu taramada görülen tüm ürünler (yeniler + daha önce çekilmiş olanlar). */
    public function products(): HasMany
    {
        return $this->hasMany(TelegramChannelProduct::class);
    }

    /** Yalnızca bu taramada ilk kez ortaya çıkanlar. Detay sayfasının varsayılanı. */
    public function newProducts(): HasMany
    {
        return $this->hasMany(TelegramChannelProduct::class, 'first_telegram_scan_id');
    }

    /** Daha önce çekilmiş, bu taramada tekrar görülen ürün sayısı. */
    public function existingCount(): int
    {
        return max(0, (int) $this->found_count - (int) $this->new_count);
    }

    /** Kaç kanaldan kaçı tarandı: ilerleme çubuğu için. */
    public function channelTotal(): int
    {
        return count($this->channels ?? []);
    }

    public function progressPercent(): int
    {
        $total = $this->channelTotal();

        if ($total === 0) {
            return 100;
        }

        return (int) round(min($total, (int) $this->cursor) / $total * 100);
    }

    /**
     * İşlenmekte olan kanalın çubuktaki payı.
     *
     * Bir kanalın içinde nerede olduğumuzu bilmiyoruz (tek istek, bölünmüyor).
     * Uydurma yüzde göstermek yerine bu dilim hareketli çizgilerle çiziliyor:
     * dolu kısım gerçekten biteni, hareketli kısım "şu an burada çalışılıyor"u
     * anlatıyor.
     */
    public function activeSlicePercent(): int
    {
        $total = $this->channelTotal();

        if ($total === 0 || ! $this->isRunning() || (int) $this->cursor >= $total) {
            return 0;
        }

        return (int) round(100 / $total);
    }

    public function isRunning(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }

    /** Tarama sürerken gösterilen satır: "2/3 kanal · @rosearyaa taranıyor" */
    public function progressLabel(): string
    {
        $channels = array_values($this->channels ?? []);
        $done = min((int) $this->cursor, count($channels));

        if ($done >= count($channels)) {
            return 'Tamamlanıyor...';
        }

        return $done.'/'.count($channels).' kanal · @'.$channels[$done].' taranıyor';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** Listede "@a, @b" biçiminde gösterilir. */
    public function channelsLabel(): string
    {
        return collect($this->channels ?? [])
            ->map(fn (string $username): string => '@'.$username)
            ->implode(', ');
    }
}
