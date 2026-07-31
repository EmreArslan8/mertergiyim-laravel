<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\TelegramNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Sipariş kaydedildikten sonra mağazaya Telegram bildirimi gönderir.
 *
 * Bildirim başarısız olsa bile sipariş kaybolmaz: sonuç orders tablosuna
 * yazılır, panelde "Telegram" sütunundan görülür ve elle tekrar denenebilir.
 */
class NotifyStoreOfOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(public Order $order) {}

    public function handle(TelegramNotifier $notifier): void
    {
        // Kimlik bilgileri girilmemişse tekrar denemenin anlamı yok.
        if (! $notifier->configured()) {
            $this->order->forceFill([
                'telegram_notified_at' => null,
                'telegram_error' => 'Telegram Bot API kimlik bilgileri tanımlı değil.',
            ])->save();

            return;
        }

        $notifier->sendOrderNotification($this->order->loadMissing('items'));

        $this->order->forceFill([
            'telegram_notified_at' => now(),
            'telegram_error' => null,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        $this->order->forceFill([
            'telegram_error' => mb_substr($exception->getMessage(), 0, 500),
        ])->save();
    }
}
