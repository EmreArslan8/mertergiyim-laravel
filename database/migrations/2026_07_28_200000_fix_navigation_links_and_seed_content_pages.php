<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * İki veri düzeltmesi:
 *
 * 1. Footer bilgilendirme linkleri content_pages kaydı olmadığı için 404
 *    veriyordu; dört sayfa Türkçe taslak metinle oluşturuluyor. Diğer 9 dil
 *    panelden kaydedince otomatik çeviriyle dolar.
 * 2. Header'da "Yeni Gelenler", "Kategoriler" ve footer'da "Hakkımızda"
 *    linklerinin url'i "#" kalmıştı; gerçek hedeflere bağlanıyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->pages() as $slug => [$title, $body, $order]) {
            if (DB::table('content_pages')->where('slug', $slug)->exists()) {
                continue;
            }

            DB::table('content_pages')->insert([
                'id' => (string) Str::uuid(),
                'slug' => $slug,
                'title' => json_encode(['tr' => $title], JSON_UNESCAPED_UNICODE),
                'content' => json_encode(['tr' => $body], JSON_UNESCAPED_UNICODE),
                'active' => true,
                'sort_order' => $order,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $links = [
            ['header', 'new', '/tr#urunler'],
            ['header', 'categories', '/tr#urunler'],
            ['footer', 'about', '/tr/hakkimizda'],
            ['footer', 'privacy', '/tr/gizlilik-politikasi'],
            ['footer', 'delivery', '/tr/teslimat-kargo'],
            ['footer', 'return', '/tr/iade-degisim'],
            ['footer', 'kvkk', '/tr/kvkk'],
        ];

        foreach ($links as [$location, $key, $url]) {
            DB::table('site_links')
                ->where('location', $location)
                ->where('link_key', $key)
                ->update(['url' => $url]);
        }
    }

    public function down(): void
    {
        DB::table('content_pages')->whereIn('slug', array_keys($this->pages()))->delete();

        DB::table('site_links')
            ->whereIn('link_key', ['new', 'categories', 'about'])
            ->update(['url' => '#']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    private function pages(): array
    {
        return [
            'hakkimizda' => [
                'Hakkımızda',
                "<p>Merter Giyim, uzun yıllardır İstanbul Merter'de kadın ve erkek hazır giyim üretimi ve toptan satışı yapmaktadır. Ürünlerimizi kendi atölyemizde üretiyor, kaliteli kumaş ve işçilikle müşterilerimize sunuyoruz.</p><p>Yurt içi ve yurt dışı siparişleriniz için WhatsApp üzerinden bize ulaşabilir, ürünlerimizi katalogdan inceleyebilirsiniz.</p>",
                1,
            ],
            'gizlilik-politikasi' => [
                'Gizlilik Politikası',
                "<p>Bu gizlilik politikası, sitemizi kullanırken paylaştığınız kişisel verilerin nasıl işlendiğini açıklar.</p><h3>Toplanan veriler</h3><p>Sipariş oluştururken ad soyad, telefon numarası, teslimat adresi ve sipariş notunuzu topluyoruz. Bu bilgiler yalnızca siparişinizin hazırlanması, kargolanması ve sizinle iletişim kurulması için kullanılır.</p><h3>Verilerin paylaşımı</h3><p>Bilgileriniz, siparişinizin teslimi için çalıştığımız kargo firması dışında üçüncü kişilerle paylaşılmaz, pazarlama amacıyla satılmaz.</p><h3>Çerezler</h3><p>Sitede sepetinizi tarayıcınızda saklamak ve dil tercihinizi hatırlamak için teknik çerezler kullanılır.</p><h3>Haklarınız</h3><p>Kişisel verilerinize erişmek, düzeltilmesini veya silinmesini istemek için iletişim sayfamızdaki kanallardan bize ulaşabilirsiniz.</p>",
                2,
            ],
            'teslimat-kargo' => [
                'Teslimat ve Kargo',
                "<p>Siparişleriniz, sipariş onayından sonra hazırlanıp anlaşmalı kargo firmasına teslim edilir.</p><h3>Hazırlık süresi</h3><p>Stokta bulunan ürünler genellikle 1-3 iş günü içinde kargoya verilir. Yoğun dönemlerde bu süre uzayabilir; gecikme durumunda sizi telefonla bilgilendiririz.</p><h3>Teslim süresi</h3><p>Yurt içi teslimatlar kargoya verildikten sonra ortalama 1-3 iş günü sürer. Yurt dışı gönderilerde süre ülkeye göre değişir.</p><h3>Kargo takibi</h3><p>Siparişiniz kargoya verildiğinde takip numaranız sipariş takibi sayfasında görünür. Sipariş numaranız veya takip kodunuzla durumu her zaman sorgulayabilirsiniz.</p>",
                3,
            ],
            'iade-degisim' => [
                'İade ve Değişim',
                "<p>Ürünlerimizden memnun kalmanız bizim için önemlidir.</p><h3>Süre</h3><p>Ürünü teslim aldığınız tarihten itibaren 14 gün içinde iade veya değişim talebinde bulunabilirsiniz.</p><h3>Koşullar</h3><p>İade edilecek ürünün kullanılmamış, yıkanmamış ve etiketleri sökülmemiş olması, orijinal ambalajıyla gönderilmesi gerekir. Hijyen gereği iç giyim ürünlerinde iade kabul edilmemektedir.</p><h3>Nasıl yapılır</h3><p>İade veya beden değişimi için önce iletişim sayfamızdaki WhatsApp numarasından bize ulaşın; onay sonrası kargo yönlendirmesini paylaşırız. Ayıplı ürün kaynaklı iadelerde kargo ücreti tarafımıza aittir.</p>",
                4,
            ],
            'kvkk' => [
                'KVKK Aydınlatma Metni',
                "<p>6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) kapsamında veri sorumlusu sıfatıyla hazırlanmıştır.</p><h3>İşlenen veriler ve amacı</h3><p>Ad soyad, telefon ve adres bilgileriniz; siparişinizin oluşturulması, hazırlanması, kargolanması ve müşteri iletişiminin yürütülmesi amacıyla işlenir.</p><h3>Hukuki sebep</h3><p>Veriler, sözleşmenin kurulması ve ifası için gerekli olması hukuki sebebine dayanarak, elektronik ortamda otomatik yollarla işlenir.</p><h3>Aktarım</h3><p>Verileriniz yalnızca teslimatın gerçekleştirilmesi amacıyla kargo firmasına aktarılır.</p><h3>KVKK madde 11 hakları</h3><p>Kişisel verilerinizin işlenip işlenmediğini öğrenme, işlenmişse bilgi talep etme, düzeltilmesini, silinmesini veya yok edilmesini isteme haklarınız bulunmaktadır. Taleplerinizi iletişim sayfamızdaki kanallardan iletebilirsiniz.</p>",
                5,
            ],
        ];
    }
};
