<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->translations() as $key => $translations) {
            $row = DB::table('site_links')
                ->where('location', 'header')
                ->where('link_key', $key)
                ->first(['label']);

            if (! $row) {
                continue;
            }

            $existing = is_string($row->label)
                ? json_decode($row->label, true)
                : (array) $row->label;
            $existing = is_array($existing)
                ? array_filter($existing, fn ($value) => trim((string) $value) !== '')
                : [];

            // site_links tablosunda updated_at kolonu yok.
            DB::table('site_links')
                ->where('location', 'header')
                ->where('link_key', $key)
                ->update([
                    'label' => json_encode(
                        array_merge($translations, $existing),
                        JSON_UNESCAPED_UNICODE,
                    ),
                ]);
        }
    }

    public function down(): void
    {
        // İçerik çevirileri geri alınırken silinmez.
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function translations(): array
    {
        return [
            'home' => [
                'tr' => 'Anasayfa', 'en' => 'Home', 'ar' => 'الرئيسية', 'ru' => 'Главная',
                'fa' => 'صفحه اصلی', 'uk' => 'Головна', 'fr' => 'Accueil', 'de' => 'Startseite',
                'es' => 'Inicio', 'it' => 'Home',
            ],
            'new' => [
                'tr' => 'Yeni Gelenler', 'en' => 'New Arrivals', 'ar' => 'وصل حديثاً', 'ru' => 'Новинки',
                'fa' => 'جدیدترین‌ها', 'uk' => 'Новинки', 'fr' => 'Nouveautés', 'de' => 'Neuheiten',
                'es' => 'Novedades', 'it' => 'Nuovi Arrivi',
            ],
            'categories' => [
                'tr' => 'Kategoriler', 'en' => 'Categories', 'ar' => 'الفئات', 'ru' => 'Категории',
                'fa' => 'دسته‌بندی‌ها', 'uk' => 'Категорії', 'fr' => 'Catégories', 'de' => 'Kategorien',
                'es' => 'Categorías', 'it' => 'Categorie',
            ],
            'tracking' => [
                'tr' => 'Sipariş Takibi', 'en' => 'Order Tracking', 'ar' => 'تتبع الطلب',
                'ru' => 'Отслеживание заказа', 'fa' => 'پیگیری سفارش', 'uk' => 'Відстеження замовлення',
                'fr' => 'Suivi de commande', 'de' => 'Sendungsverfolgung',
                'es' => 'Seguimiento del pedido', 'it' => 'Traccia l’ordine',
            ],
        ];
    }
};
