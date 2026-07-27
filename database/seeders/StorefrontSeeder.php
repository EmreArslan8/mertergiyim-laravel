<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Color;
use App\Models\Currency;
use App\Models\HeroSlide;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SiteLink;
use App\Models\SiteSetting;
use App\Models\Size;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * supabase/products_seed.sql + languages.sql + site_links.sql + schema.sql
 * içindeki başlangıç verisinin lokal (SQLite) karşılığı.
 */
class StorefrontSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLanguages();
        $this->seedCurrencies();
        $this->seedSizesAndColors();
        $this->seedCategories();
        $this->seedProducts();
        $this->seedHeroSlides();
        $this->seedSiteLinks();
        $this->seedSiteSettings();
        $this->seedDemoOrder();
    }

    private function seedLanguages(): void
    {
        $languages = [
            ['tr', 'Türkçe', 1], ['en', 'English', 2], ['ar', 'العربية', 3], ['ru', 'Русский', 4],
            ['fa', 'فارسی', 5], ['uk', 'Українська', 6], ['fr', 'Français', 7], ['de', 'Deutsch', 8],
            ['es', 'Español', 9], ['it', 'Italiano', 10],
        ];

        foreach ($languages as [$code, $name, $order]) {
            Language::updateOrCreate(['code' => $code], ['name' => $name, 'sort_order' => $order, 'active' => true]);
        }
    }

    private function seedCurrencies(): void
    {
        Currency::updateOrCreate(['code' => 'TRY'], ['symbol' => 'TL', 'position' => 'suffix', 'is_default' => true, 'sort_order' => 1]);
        Currency::updateOrCreate(['code' => 'USD'], ['symbol' => '$', 'position' => 'prefix', 'is_default' => false, 'sort_order' => 2]);
        Currency::updateOrCreate(['code' => 'EUR'], ['symbol' => '€', 'position' => 'suffix', 'is_default' => false, 'sort_order' => 3]);
    }

    private function seedSizesAndColors(): void
    {
        foreach (['XS', 'S', 'M', 'L', 'X', 'XL', 'XXL', '3XL'] as $index => $name) {
            Size::updateOrCreate(['name' => $name], ['sort_order' => $index + 1, 'active' => true]);
        }

        $colors = [
            ['Beyaz', '#ffffff'], ['Sarı', '#ffff01'], ['Siyah', '#000000'], ['Mavi', '#0044ff'],
            ['Turkuaz', '#3fe0d0'], ['Bordo', '#a22c2a'], ['Gold', '#f5ed71'], ['Yeşil', '#008001'],
        ];

        foreach ($colors as $index => [$name, $hex]) {
            Color::updateOrCreate(['name' => $name], ['hex' => $hex, 'sort_order' => $index + 1, 'active' => true]);
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            ['Kadın Giyim', 'kadin-giyim'],
            ['Elbiseler', 'elbiseler'],
            ['Takımlar', 'takimlar'],
            ['Keten', 'keten'],
        ];

        foreach ($categories as [$name, $slug]) {
            Category::updateOrCreate(['slug' => $slug], ['name' => $name, 'active' => true]);
        }
    }

    private function seedProducts(): void
    {
        $description = "Satışlarımız toptandır. Ürünün serisi 6'lıdır. Kargo alıcıya aittir.";

        $rows = [
            ['elbiseler', '01', 'kot-garnili-papertouch-kumas-elbise', 'Kot Garnili Papertouch Kumaş Elbise', 23],
            ['takimlar', '02', 'keten-kumas-sortlu-takim', 'Keten Kumaş Şortlu Takım', 23],
            ['takimlar', '03', 'keten-dantelli-bluz-etek-takim', 'Keten Dantelli Bluz&Etek Takım', 30],
            ['takimlar', '04', 'zimmerman-desen-keten-takim', 'Zimmerman Desen Keten Takım', 24],
            ['elbiseler', '05', 'zimmerman-model-keten-elbise', 'Zimmerman Model Keten Elbise', 21],
            ['takimlar', '06', 'keten-kumas-sortlu-takim-2', 'Keten Kumaş Şortlu Takım', 23],
            ['elbiseler', '07', 'keten-kumas-cicek-desenli-elbise', 'Keten Kumaş Çiçek Desenli Elbise', 21],
            ['takimlar', '08', 'modal-kumas-salvar-takim', 'Modal Kumaş Şalvar Takım', 18],
            ['elbiseler', '09', 'zimmerman-elbise-keten-kumas-1', 'Zimmerman Elbise Keten Kumaş', 25],
            ['elbiseler', '10', 'zimmerman-elbise-keten-kumas-2', 'Zimmerman Elbise Keten Kumaş', 25],
            ['elbiseler', '11', 'dugumlu-kalp-yaka-keten-elbise', 'Düğümlü Kalp Yaka Keten Elbise', 23],
            ['elbiseler', '12', 'ithal-brode-nakis-detayli-elbise', 'İthal Brode Nakış Detaylı Elbise', 30],
        ];

        $categoryIds = Category::pluck('id', 'slug');
        $sizeIds = Size::pluck('id', 'name');
        $colorIds = Color::pluck('id', 'name');
        $createdAt = now();

        foreach ($rows as $index => [$categorySlug, $code, $slug, $name, $price]) {
            $product = Product::updateOrCreate(['code' => $code], [
                'category_id' => $categoryIds[$categorySlug] ?? null,
                'slug' => $slug,
                'name' => ['tr' => $name],
                'description' => ['tr' => $description],
                'price' => $price,
                // products_seed.sql sonundaki "update ... set currency='TRY'" ile aynı sonuç.
                'currency' => 'TRY',
                'stock_status' => 'in_stock',
                'active' => true,
                'created_at' => $createdAt->copy()->addSeconds($index),
                'updated_at' => $createdAt,
            ]);

            // Görseller: gerçek storage_path'ler Supabase'de. Lokalde iki adet
            // yer tutucu path bırakıyoruz ki hover/galeri davranışı test edilebilsin.
            $product->images()->delete();
            foreach ([1, 2, 3] as $position) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'storage_path' => "seed/{$slug}-{$position}.jpg",
                    'alt' => ['tr' => $name],
                    'sort_order' => $position,
                    'is_primary' => $position === 1,
                ]);
            }

            $product->variants()->delete();
            foreach (['S', 'M', 'L'] as $sizeName) {
                foreach (['Beyaz', 'Siyah', 'Mavi'] as $colorName) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'size_id' => $sizeIds[$sizeName] ?? null,
                        'color_id' => $colorIds[$colorName] ?? null,
                        'stock_quantity' => 10,
                    ]);
                }
            }
        }
    }

    private function seedHeroSlides(): void
    {
        HeroSlide::query()->delete();

        HeroSlide::create([
            'title' => [
                'tr' => "Yeni Gelen\nÜRÜNLER",
                'en' => "New Arrivals\nPRODUCTS",
                'ar' => "وصل حديثاً\nالمنتجات",
                'ru' => "Новые поступления\nТОВАРЫ",
                'fa' => "تازه رسیده\nمحصولات",
                'uk' => "Нові надходження\nТОВАРИ",
                'fr' => "Nouveautés\nPRODUITS",
                'de' => "Neu eingetroffen\nPRODUKTE",
                'es' => "Novedades\nPRODUCTOS",
                'it' => "Nuovi arrivi\nPRODOTTI",
            ],
            'image_path' => 'seed/hero-1.jpg',
            'button_text' => [
                'tr' => 'İNCELE', 'en' => 'EXPLORE', 'ar' => 'استكشف', 'ru' => 'СМОТРЕТЬ', 'fa' => 'مشاهده',
                'uk' => 'ПЕРЕГЛЯНУТИ', 'fr' => 'DÉCOUVRIR', 'de' => 'ENTDECKEN', 'es' => 'DESCUBRIR', 'it' => 'SCOPRI',
            ],
            'button_url' => '/tr#urunler',
            'sort_order' => 1,
            'active' => true,
        ]);

        HeroSlide::create([
            'title' => ['tr' => "Sezon\nİNDİRİMİ", 'en' => "Season\nSALE"],
            'image_path' => 'seed/hero-2.jpg',
            'button_text' => ['tr' => 'KEŞFET', 'en' => 'DISCOVER'],
            'button_url' => '/tr#urunler',
            'sort_order' => 2,
            'active' => true,
        ]);
    }

    private function seedSiteLinks(): void
    {
        $links = [
            ['header', 'home', 'Anasayfa', '/tr', 1],
            ['header', 'new', 'Yeni Gelenler', '/tr#urunler', 2],
            ['header', 'categories', 'Kategoriler', '/tr#urunler', 3],
            ['header', 'tracking', 'Sipariş Takibi', '/tr/siparis-takibi', 4],
            ['header', 'contact', 'İletişim', '/tr#iletisim', 5],
            ['header', 'cart', 'Sepet', '/tr#sepet', 6],
            ['footer', 'about', 'Hakkımızda', '/tr#iletisim', 1],
            ['footer', 'privacy', 'Gizlilik Politikası', '/tr/gizlilik-politikasi', 2],
            ['footer', 'delivery', 'Teslimat ve Kargo', '/tr/teslimat-kargo', 3],
            ['footer', 'return', 'İade ve Değişim', '/tr/iade-degisim', 4],
            ['footer', 'kvkk', 'KVKK', '/tr/kvkk', 5],
            ['footer', 'whatsapp', 'WhatsApp', 'https://wa.me/905555555555', 6],
            ['footer', 'instagram', 'Instagram', 'https://instagram.com', 7],
        ];

        foreach ($links as [$location, $key, $label, $url, $order]) {
            SiteLink::updateOrCreate(
                ['location' => $location, 'link_key' => $key],
                ['label' => ['tr' => $label], 'url' => $url, 'sort_order' => $order, 'active' => true],
            );
        }
    }

    private function seedSiteSettings(): void
    {
        $perLocale = [
            'tr' => [
                'footerBrand' => 'Merter Giyim',
                'footerDescription' => 'Merter’den Türkiye’nin her noktasına toptan ve perakende kadın giyim.',
                'footerInfoTitle' => 'Bilgilendirmeler',
                'footerAddress' => 'MERTER / İSTANBUL',
                'copyright' => '© '.date('Y').' Merter Giyim. Tüm hakları saklıdır.',
            ],
            'en' => [
                'footerBrand' => 'Merter Clothing',
                'footerDescription' => 'Wholesale and retail women’s clothing shipped worldwide from Merter, Istanbul.',
                'footerInfoTitle' => 'Information',
                'footerAddress' => 'MERTER / ISTANBUL',
                'copyright' => '© '.date('Y').' Merter Clothing. All rights reserved.',
            ],
        ];

        foreach (config('storefront.locales') as $locale) {
            $perLocale[$locale] ??= $perLocale['en'];
        }

        SiteSetting::updateOrCreate(['key' => 'storefront'], ['value' => $perLocale, 'updated_at' => now()]);
    }

    private function seedDemoOrder(): void
    {
        $order = Order::updateOrCreate(['order_number' => 'MG-20260727-8H4K2P9X'], [
            'tracking_code' => 'TK9X4M2P',
            'customer_name' => 'Ayşe Yılmaz',
            'phone' => '05320000000',
            'address' => 'Merter, İstanbul',
            'status' => 'shipped',
            'total' => 690,
            'cargo_company' => 'Aras Kargo',
            'cargo_tracking_url' => 'https://kargotakip.araskargo.com.tr/',
            'created_at' => now(),
        ]);

        $order->items()->delete();

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_name' => 'Keten Kumaş Şortlu Takım',
            'product_code' => '02',
            'size' => 'M',
            'color' => 'Beyaz',
            'quantity' => 6,
            'created_at' => now(),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_name' => 'Zimmerman Model Keten Elbise',
            'product_code' => '05',
            'size' => 'L',
            'color' => 'Siyah',
            'quantity' => 6,
            'created_at' => now()->addSecond(),
        ]);
    }
}
