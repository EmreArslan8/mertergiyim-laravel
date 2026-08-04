<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderCodeService
{
    /**
     * @return array{order_number: string, tracking_code: string}
     */
    public function generate(): array
    {
        do {
            $orderNumber = 'MG-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        do {
            $trackingCode = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Order::query()->where('tracking_code', $trackingCode)->exists());

        return [
            'order_number' => $orderNumber,
            'tracking_code' => $trackingCode,
        ];
    }
}
