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

    /**
     * Siparişin normal akışı. "İptal" bu akışın dışında, ayrı bir çıkış.
     *
     * @return list<string>
     */
    public static function flow(): array
    {
        return ['new', 'confirmed', 'paid', 'preparing', 'shipped', 'completed'];
    }

    /**
     * Akıştaki bir sonraki durum; son adımda veya iptalde null.
     */
    public static function next(?string $status): ?string
    {
        $flow = self::flow();
        $index = array_search($status, $flow, strict: true);

        if ($index === false) {
            return null;
        }

        return $flow[$index + 1] ?? null;
    }

    /**
     * Akışta kaçıncı adımdayız? İptal edilmiş sipariş akışın dışında (-1).
     */
    public static function step(?string $status): int
    {
        $index = array_search($status, self::flow(), strict: true);

        return $index === false ? -1 : $index;
    }

    /**
     * Kargo bilgisi ne zaman girilebilir?
     *
     * Sipariş hazırlanmaya başlamadan kargo firması seçmek erken: sipariş
     * onaylanmadan/ödenmeden kargoya verilmiyor, iptal edilen siparişe de
     * kargo girilmiyor. "Hazırlanıyor"dan itibaren açılır — böylece
     * "Kargoya verildi" adımına geçmeden önce firma girilebilir.
     */
    public static function allowsCargo(?string $status): bool
    {
        return in_array($status, ['preparing', 'shipped', 'completed'], strict: true);
    }

    public static function isCancelled(?string $status): bool
    {
        return $status === 'cancelled';
    }

    /**
     * Renk her duruma ayrı değil, duruma karşılık gelen ANLAMA verilir:
     * mavi = ilgi bekliyor, gri = akış içinde, yeşil = bitti, kırmızı = iptal.
     * Yedi ayrı renk, listede rozetleri okunur olmaktan çıkarıp gökkuşağına
     * çeviriyordu; kargonun yolda olduğu bilgisi zaten Kargo sütununda var.
     */
    public static function color(?string $status): string
    {
        return match ($status) {
            'new' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            'confirmed', 'paid', 'preparing', 'shipped' => 'gray',
            default => 'gray',
        };
    }
}
