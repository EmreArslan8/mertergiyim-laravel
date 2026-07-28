<?php

namespace App\Services;

use App\Models\Order;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Yeni sipariş açıldığında mağazaya WhatsApp bildirimi gönderir
 * (Meta WhatsApp Cloud API).
 *
 * Meta'nın 24 saat kuralı gereği iş tarafından başlatılan mesajlar onaylı
 * şablonla gönderilmek zorunda; varsayılan mod bu yüzden 'template'. Şablonun
 * gövdesinde sırasıyla dört değişken beklenir:
 *
 *   {{1}} sipariş numarası, {{2}} müşteri adı,
 *   {{3}} telefon, {{4}} tutar + para birimi
 */
class WhatsAppNotifier
{
    public function configured(): bool
    {
        return $this->phoneNumberId() !== '' && $this->token() !== '' && $this->recipient() !== '';
    }

    /**
     * Bildirim gönderir. Başarısızlıkta istisna fırlatır; çağıran taraf
     * (job) hatayı siparişe yazar. Sipariş akışını asla bloklamaz.
     */
    public function sendOrderNotification(Order $order): void
    {
        if (! $this->configured()) {
            throw new RuntimeException('WhatsApp Cloud API kimlik bilgileri eksik.');
        }

        $endpoint = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            (string) config('storefront.whatsapp.api_version'),
            $this->phoneNumberId(),
        );

        $response = Http::timeout(20)
            ->withToken($this->token())
            ->post($endpoint, $this->payload($order));

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? 'WhatsApp bildirimi gönderilemedi.';

            Log::warning('WhatsApp sipariş bildirimi başarısız', [
                'order_number' => $order->order_number,
                'status' => $response->status(),
                'error' => $message,
            ]);

            throw new RuntimeException($message);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Order $order): array
    {
        $base = [
            'messaging_product' => 'whatsapp',
            'to' => $this->recipient(),
        ];

        if ((string) config('storefront.whatsapp.mode') === 'text') {
            return $base + [
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $this->textBody($order)],
            ];
        }

        return $base + [
            'type' => 'template',
            'template' => [
                'name' => (string) config('storefront.whatsapp.template'),
                'language' => ['code' => (string) config('storefront.whatsapp.template_language')],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(
                        fn (string $value) => ['type' => 'text', 'text' => $value],
                        $this->templateParameters($order),
                    ),
                ]],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function templateParameters(Order $order): array
    {
        return [
            (string) $order->order_number,
            (string) $order->customer_name,
            (string) $order->phone,
            number_format((float) $order->total, 2, ',', '.').' '.$order->currency,
        ];
    }

    private function textBody(Order $order): string
    {
        $lines = [
            'Yeni sipariş: '.$order->order_number,
            'Müşteri: '.$order->customer_name,
            'Telefon: '.$order->phone,
            'Tutar: '.number_format((float) $order->total, 2, ',', '.').' '.$order->currency,
            'Takip kodu: '.$order->tracking_code,
        ];

        foreach ($order->items as $item) {
            $detail = array_filter([$item->size, $item->color]);

            $lines[] = '• '.$item->quantity.' x '.$item->product_name
                .($detail ? ' ('.implode(' / ', $detail).')' : '');
        }

        $lines[] = 'Adres: '.$order->address;

        if ($order->note) {
            $lines[] = 'Not: '.$order->note;
        }

        return implode("\n", $lines);
    }

    private function phoneNumberId(): string
    {
        return trim((string) config('storefront.whatsapp.phone_number_id'));
    }

    private function token(): string
    {
        return trim((string) config('storefront.whatsapp.token'));
    }

    private function recipient(): string
    {
        $number = (string) config('storefront.whatsapp.recipient')
            ?: (string) config('storefront.whatsapp_number');

        return PhoneNumber::normalize($number);
    }
}
