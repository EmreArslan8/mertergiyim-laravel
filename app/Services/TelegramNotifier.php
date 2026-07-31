<?php

namespace App\Services;

use App\Models\Order;
use App\Support\BrandSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Yeni sipariş açıldığında mağazaya Telegram bildirimi gönderir
 * (Telegram Bot API).
 *
 * WhatsApp'ın aksine şablon/onay/24 saat kuralı yok: bot token ve chat id
 * tanımlıysa düz HTML mesajı anında gönderilir. Kimlik bilgileri boşken
 * bildirim sessizce atlanır, sipariş normal kaydedilir.
 */
class TelegramNotifier
{
    public function configured(): bool
    {
        return $this->token() !== '' && $this->chatId() !== '';
    }

    /**
     * Bildirim gönderir. Başarısızlıkta istisna fırlatır; çağıran taraf
     * (job) hatayı siparişe yazar. Sipariş akışını asla bloklamaz.
     */
    public function sendOrderNotification(Order $order): void
    {
        if (! $this->configured()) {
            throw new RuntimeException('Telegram Bot API kimlik bilgileri eksik.');
        }

        $endpoint = sprintf('https://api.telegram.org/bot%s/sendMessage', $this->token());

        $response = Http::timeout(20)->post($endpoint, [
            'chat_id' => $this->chatId(),
            'text' => $this->messageBody($order),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        if (! $response->successful()) {
            $message = $response->json('description') ?? 'Telegram bildirimi gönderilemedi.';

            Log::warning('Telegram sipariş bildirimi başarısız', [
                'order_number' => $order->order_number,
                'status' => $response->status(),
                'error' => $message,
            ]);

            throw new RuntimeException($message);
        }
    }

    private function messageBody(Order $order): string
    {
        $lines = [
            '<b>🛍 Yeni sipariş:</b> '.$this->esc($order->order_number),
            '<b>Müşteri:</b> '.$this->esc($order->customer_name),
            '<b>Telefon:</b> '.$this->esc($order->phone),
            '<b>Tutar:</b> '.$this->esc(number_format((float) $order->total, 2, ',', '.').' '.$order->currency),
            '<b>Takip kodu:</b> '.$this->esc($order->tracking_code),
            '',
        ];

        foreach ($order->items as $item) {
            $detail = array_filter([$item->size, $item->color]);

            $lines[] = '• '.$this->esc($item->quantity.' x '.$item->product_name
                .($detail ? ' ('.implode(' / ', $detail).')' : ''));
        }

        $lines[] = '';
        $lines[] = '<b>Adres:</b> '.$this->esc($order->address);

        if ($order->note) {
            $lines[] = '<b>Not:</b> '.$this->esc($order->note);
        }

        return implode("\n", $lines);
    }

    /**
     * Telegram HTML parse_mode için yalnızca &, <, > kaçırılmalı.
     */
    private function esc(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');
    }

    private function token(): string
    {
        return trim((string) config('storefront.telegram.token'));
    }

    private function chatId(): string
    {
        $chatId = (string) BrandSettings::general('orderNotificationChatId')
            ?: (string) config('storefront.telegram.chat_id');

        return trim($chatId);
    }
}
