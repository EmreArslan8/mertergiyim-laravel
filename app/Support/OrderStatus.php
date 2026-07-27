<?php

namespace App\Support;

/**
 * orders.status değerleri ve panel gösterimi (kaynak OrdersManager.tsx).
 */
class OrderStatus
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'new' => 'Yeni',
            'confirmed' => 'Onaylandı',
            'paid' => 'Ödendi',
            'preparing' => 'Hazırlanıyor',
            'shipped' => 'Kargoda',
            'completed' => 'Tamamlandı',
            'cancelled' => 'İptal',
        ];
    }

    public static function label(?string $status): string
    {
        return self::labels()[$status] ?? (string) $status;
    }

    public static function color(?string $status): string
    {
        return match ($status) {
            'new' => 'info',
            'confirmed', 'paid' => 'primary',
            'preparing', 'shipped' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
