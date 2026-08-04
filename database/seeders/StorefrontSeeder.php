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
            ['kadin-giyim', [
                'tr' => 'Kadın Giyim',
                'en' => "Women's Clothing",
                'ar' => 'ملابس نسائية',
                'ru' => 'Женская одежда',
                'fa' => 'پوشاک زنانه',
                'uk' => 'Жіночий одяг',
                'fr' => 'Vêtements pour femmes',
                'de' => 'Damenbekleidung',
                'es' => 'Ropa de mujer',
                'it' => 'Abbigliamento donna',
            ]],
            ['elbiseler', [
                'tr' => 'Elbiseler',
                'en' => 'Dresses',
                'ar' => 'فساتين',
                'ru' => 'Платья',
                'fa' => 'پیراهن‌ها',
                'uk' => 'Сукні',
                'fr' => 'Robes',
                'de' => 'Kleider',
                'es' => 'Vestidos',
                'it' => 'Abiti',
            ]],
            ['takimlar', [
                'tr' => 'Takımlar',
                'en' => 'Sets',
                'ar' => 'أطقم',
                'ru' => 'Комплекты',
                'fa' => 'ست‌ها',
                'uk' => 'Комплекти',
                'fr' => 'Ensembles',
                'de' => 'Sets',
                'es' => 'Conjuntos',
                'it' => 'Completi',
            ]],
            ['keten', [
                'tr' => 'Keten',
                'en' => 'Linen',
                'ar' => 'كتان',
                'ru' => 'Лён',
                'fa' => 'کتان',
                'uk' => 'Льон',
                'fr' => 'Lin',
                'de' => 'Leinen',
                'es' => 'Lino',
                'it' => 'Lino',
            ]],
        ];

        foreach ($categories as [$slug, $translations]) {
            Category::updateOrCreate(['slug' => $slug], [
                'name' => $translations['tr'],
                'name_i18n' => $translations,
                'active' => true,
            ]);
        }
    }

    private function seedProducts(): void
    {
        $description = $this->seedProductDescriptionTranslations();
        $translatedNames = $this->seedProductNameTranslations();

        // Ürün adı benzersizdir; aynı adla ikinci satır eklenemez.
        $rows = [
            ['elbiseler', '01', 'kot-garnili-papertouch-kumas-elbise', 'Kot Garnili Papertouch Kumaş Elbise', 23],
            ['takimlar', '02', 'keten-kumas-sortlu-takim', 'Keten Kumaş Şortlu Takım', 23],
            ['takimlar', '03', 'keten-dantelli-bluz-etek-takim', 'Keten Dantelli Bluz&Etek Takım', 30],
            ['takimlar', '04', 'zimmerman-desen-keten-takim', 'Zimmerman Desen Keten Takım', 24],
            ['elbiseler', '05', 'zimmerman-model-keten-elbise', 'Zimmerman Model Keten Elbise', 21],
            ['elbiseler', '07', 'keten-kumas-cicek-desenli-elbise', 'Keten Kumaş Çiçek Desenli Elbise', 21],
            ['takimlar', '08', 'modal-kumas-salvar-takim', 'Modal Kumaş Şalvar Takım', 18],
            ['elbiseler', '09', 'zimmerman-elbise-keten-kumas-1', 'Zimmerman Elbise Keten Kumaş', 25],
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
                'name' => $translatedNames[$code] ?? ['tr' => $name],
                'description' => $description,
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

    /**
     * @return array<string, array<string, string>>
     */
    private function seedProductNameTranslations(): array
    {
        return [
            '01' => [
                'tr' => 'Kot Garnili Papertouch Kumaş Elbise',
                'en' => 'Denim-Trimmed Papertouch Fabric Dress',
                'ar' => 'فستان من قماش بابرتاتش بتفاصيل جينز',
                'ru' => 'Платье из ткани Papertouch с джинсовой отделкой',
                'fa' => 'لباس پارچه‌ای پپرتاچ با حاشیه جین',
                'uk' => 'Сукня з тканини Papertouch із джинсовим оздобленням',
                'fr' => 'Robe en tissu Papertouch avec garniture en denim',
                'de' => 'Papertouch-Stoffkleid mit Jeansbesatz',
                'es' => 'Vestido de tela Papertouch con ribete de denim',
                'it' => 'Abito in tessuto Papertouch con finiture in denim',
            ],
            '02' => [
                'tr' => 'Keten Kumaş Şortlu Takım',
                'en' => 'Linen Fabric Shorts Set',
                'ar' => 'طقم شورت من قماش الكتان',
                'ru' => 'Комплект с шортами из льняной ткани',
                'fa' => 'ست شلوارک پارچه‌ای کتان',
                'uk' => 'Комплект із шортами з лляної тканини',
                'fr' => 'Ensemble short en tissu de lin',
                'de' => 'Shorts-Set aus Leinenstoff',
                'es' => 'Conjunto de shorts de lino',
                'it' => 'Completo con pantaloncini in lino',
            ],
            '03' => [
                'tr' => 'Keten Dantelli Bluz&Etek Takım',
                'en' => 'Linen Lace Blouse and Skirt Set',
                'ar' => 'طقم بلوزة وتنورة من الكتان والدانتيل',
                'ru' => 'Льняной комплект с кружевной блузкой и юбкой',
                'fa' => 'ست بلوز و دامن کتانی با توری',
                'uk' => 'Лляний комплект із мереживною блузкою та спідницею',
                'fr' => 'Ensemble blouse et jupe en lin et dentelle',
                'de' => 'Leinen-Set mit Spitzenbluse und Rock',
                'es' => 'Conjunto de blusa y falda de lino con encaje',
                'it' => 'Completo in lino con blusa e gonna in pizzo',
            ],
            '04' => [
                'tr' => 'Zimmerman Desen Keten Takım',
                'en' => 'Zimmerman Pattern Linen Set',
                'ar' => 'طقم كتان بنقشة زيمرمان',
                'ru' => 'Льняной комплект с узором Zimmerman',
                'fa' => 'ست کتانی طرح زیمِرمن',
                'uk' => 'Лляний комплект із візерунком Zimmerman',
                'fr' => 'Ensemble en lin à motif Zimmerman',
                'de' => 'Leinen-Set mit Zimmerman-Muster',
                'es' => 'Conjunto de lino con estampado Zimmerman',
                'it' => 'Completo in lino con fantasia Zimmerman',
            ],
            '05' => [
                'tr' => 'Zimmerman Model Keten Elbise',
                'en' => 'Zimmerman-Style Linen Dress',
                'ar' => 'فستان كتان بقصة زيمرمان',
                'ru' => 'Льняное платье в стиле Zimmerman',
                'fa' => 'پیراهن کتانی مدل زیمِرمن',
                'uk' => 'Лляна сукня в стилі Zimmerman',
                'fr' => 'Robe en lin style Zimmerman',
                'de' => 'Leinenkleid im Zimmerman-Stil',
                'es' => 'Vestido de lino estilo Zimmerman',
                'it' => 'Abito in lino stile Zimmerman',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function seedProductDescriptionTranslations(): array
    {
        return [
            'tr' => "Satışlarımız toptandır. Ürünün serisi 6'lıdır. Kargo alıcıya aittir.",
            'en' => 'We sell wholesale. Each product set contains 6 pieces. Shipping costs are paid by the buyer.',
            'ar' => 'نبيع بالجملة. تتكون سلسلة المنتج من 6 قطع. يتحمل المشتري تكاليف الشحن.',
            'ru' => 'Мы продаём оптом. В комплект входит 6 единиц. Доставку оплачивает покупатель.',
            'fa' => 'فروش ما به‌صورت عمده است. هر سری محصول شامل ۶ عدد است. هزینه ارسال بر عهده خریدار است.',
            'uk' => 'Ми продаємо оптом. Комплект складається з 6 одиниць. Доставку оплачує покупець.',
            'fr' => 'Nous vendons en gros. Chaque série comprend 6 pièces. Les frais de livraison sont à la charge de l’acheteur.',
            'de' => 'Wir verkaufen im Großhandel. Eine Produktserie enthält 6 Teile. Die Versandkosten trägt der Käufer.',
            'es' => 'Vendemos al por mayor. Cada serie contiene 6 unidades. Los gastos de envío corren a cargo del comprador.',
            'it' => 'Vendiamo all’ingrosso. Ogni serie contiene 6 pezzi. Le spese di spedizione sono a carico dell’acquirente.',
        ];
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
            'button_url' => '/#urunler',
            'sort_order' => 1,
            'active' => true,
        ]);

        HeroSlide::create([
            'title' => ['tr' => "Sezon\nİNDİRİMİ", 'en' => "Season\nSALE"],
            'image_path' => 'seed/hero-2.jpg',
            'button_text' => ['tr' => 'KEŞFET', 'en' => 'DISCOVER'],
            'button_url' => '/#urunler',
            'sort_order' => 2,
            'active' => true,
        ]);
    }

    private function seedSiteLinks(): void
    {
        $headerTranslations = $this->headerLinkTranslations();
        $footerTranslations = $this->footerLinkTranslations();
        $links = [
            ['header', 'home', 'Anasayfa', '/', 1],
            ['header', 'new', 'Yeni Gelenler', '/#urunler', 2],
            ['header', 'categories', 'Kategoriler', '/#urunler', 3],
            ['header', 'tracking', 'Sipariş Takibi', '/siparis-takibi', 4],
            ['header', 'contact', 'İletişim', '/#iletisim', 5],
            ['header', 'cart', 'Sepet', '/#sepet', 6],
            ['footer', 'about', 'Hakkımızda', '/hakkimizda', 1],
            ['footer', 'distance_sale', 'Mesafeli Satış Sözleşmesi', '/mesafeli-satis-sozlesmesi', 2],
            ['footer', 'pre_information', 'Ön Bilgilendirme Formu', '/on-bilgilendirme-formu', 3],
            ['footer', 'privacy', 'Gizlilik Politikası', '/gizlilik-politikasi', 4],
            ['footer', 'delivery', 'Teslimat ve Kargo Politikası', '/teslimat-kargo', 5],
            ['footer', 'refund_policy', 'İptal ve Geri Ödeme Politikası', '/iptal-iade-politikasi', 6],
            ['footer', 'cookie_policy', 'Çerez Politikası', '/cerez-politikasi', 7],
            ['footer', 'terms', 'Kullanım Koşulları', '/kullanim-kosullari', 8],
            ['footer', 'return', 'İade ve Değişim Koşulları', '/iade-degisim', 9],
            ['footer', 'kvkk', 'KVKK Aydınlatma Metni', '/kvkk', 10],
            ['footer', 'whatsapp', 'WhatsApp', 'https://wa.me/905555555555', 11],
            ['footer', 'instagram', 'Instagram', 'https://instagram.com', 12],
        ];

        foreach ($links as [$location, $key, $label, $url, $order]) {
            SiteLink::updateOrCreate(
                ['location' => $location, 'link_key' => $key],
                [
                    'label' => $location === 'header'
                        ? ($headerTranslations[$key] ?? ['tr' => $label])
                        : ($footerTranslations[$key] ?? ['tr' => $label]),
                    'url' => $url,
                    'sort_order' => $order,
                    'active' => true,
                ],
            );
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function headerLinkTranslations(): array
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
            'blog' => [
                'tr' => 'Blog', 'en' => 'Blog', 'ar' => 'المدونة', 'ru' => 'Блог', 'fa' => 'وبلاگ',
                'uk' => 'Блог', 'fr' => 'Blog', 'de' => 'Blog', 'es' => 'Blog', 'it' => 'Blog',
            ],
            'multimedia' => [
                'tr' => 'Multimedya', 'en' => 'Media', 'ar' => 'الوسائط', 'ru' => 'Медиа',
                'fa' => 'رسانه', 'uk' => 'Медіа', 'fr' => 'Médias', 'de' => 'Medien',
                'es' => 'Multimedia', 'it' => 'Media',
            ],
            'contact' => [
                'tr' => 'İletişim', 'en' => 'Contact', 'ar' => 'اتصل بنا', 'ru' => 'Контакты',
                'fa' => 'تماس', 'uk' => 'Контакти', 'fr' => 'Contact', 'de' => 'Kontakt',
                'es' => 'Contacto', 'it' => 'Contatti',
            ],
            'cart' => [
                'tr' => 'Sepet', 'en' => 'Cart', 'ar' => 'السلة', 'ru' => 'Корзина',
                'fa' => 'سبد', 'uk' => 'Кошик', 'fr' => 'Panier', 'de' => 'Warenkorb',
                'es' => 'Carrito', 'it' => 'Carrello',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function footerLinkTranslations(): array
    {
        return [
            'about' => [
                'tr' => 'Hakkımızda', 'en' => 'About Us', 'ar' => 'من نحن', 'ru' => 'О нас',
                'fa' => 'درباره ما', 'uk' => 'Про нас', 'fr' => 'À propos', 'de' => 'Über uns',
                'es' => 'Sobre nosotros', 'it' => 'Chi siamo',
            ],
            'distance_sale' => [
                'tr' => 'Mesafeli Satış Sözleşmesi', 'en' => 'Distance Sales Agreement',
                'ar' => 'اتفاقية البيع عن بُعد', 'ru' => 'Договор дистанционной продажи',
                'fa' => 'قرارداد فروش از راه دور', 'uk' => 'Договір дистанційного продажу',
                'fr' => 'Contrat de vente à distance', 'de' => 'Fernabsatzvertrag',
                'es' => 'Contrato de venta a distancia', 'it' => 'Contratto di vendita a distanza',
            ],
            'pre_information' => [
                'tr' => 'Ön Bilgilendirme Formu', 'en' => 'Preliminary Information Form',
                'ar' => 'نموذج المعلومات الأولية', 'ru' => 'Форма предварительной информации',
                'fa' => 'فرم اطلاعات اولیه', 'uk' => 'Форма попередньої інформації',
                'fr' => 'Formulaire d’information préalable', 'de' => 'Vorabinformation',
                'es' => 'Formulario de información previa', 'it' => 'Modulo informativo preliminare',
            ],
            'privacy' => [
                'tr' => 'Gizlilik Politikası', 'en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية',
                'ru' => 'Политика конфиденциальности', 'fa' => 'سیاست حفظ حریم خصوصی',
                'uk' => 'Політика конфіденційності', 'fr' => 'Politique de confidentialité',
                'de' => 'Datenschutzerklärung', 'es' => 'Política de privacidad',
                'it' => 'Informativa sulla privacy',
            ],
            'delivery' => [
                'tr' => 'Teslimat ve Kargo Politikası', 'en' => 'Delivery and Shipping Policy',
                'ar' => 'سياسة التوصيل والشحن', 'ru' => 'Политика доставки',
                'fa' => 'سیاست تحویل و ارسال', 'uk' => 'Політика доставки',
                'fr' => 'Politique de livraison et d’expédition', 'de' => 'Liefer- und Versandbedingungen',
                'es' => 'Política de entrega y envío', 'it' => 'Politica di consegna e spedizione',
            ],
            'refund_policy' => [
                'tr' => 'İptal ve Geri Ödeme Politikası', 'en' => 'Cancellation and Refund Policy',
                'ar' => 'سياسة الإلغاء واسترداد الأموال', 'ru' => 'Политика отмены и возврата средств',
                'fa' => 'سیاست لغو و بازپرداخت', 'uk' => 'Політика скасування та повернення коштів',
                'fr' => 'Politique d’annulation et de remboursement',
                'de' => 'Stornierungs- und Rückerstattungsrichtlinie',
                'es' => 'Política de cancelación y reembolso',
                'it' => 'Politica di cancellazione e rimborso',
            ],
            'cookie_policy' => [
                'tr' => 'Çerez Politikası', 'en' => 'Cookie Policy', 'ar' => 'سياسة ملفات تعريف الارتباط',
                'ru' => 'Политика использования файлов cookie', 'fa' => 'سیاست کوکی‌ها',
                'uk' => 'Політика використання файлів cookie', 'fr' => 'Politique relative aux cookies',
                'de' => 'Cookie-Richtlinie', 'es' => 'Política de cookies', 'it' => 'Politica sui cookie',
            ],
            'terms' => [
                'tr' => 'Kullanım Koşulları', 'en' => 'Terms of Use', 'ar' => 'شروط الاستخدام',
                'ru' => 'Условия использования', 'fa' => 'شرایط استفاده', 'uk' => 'Умови використання',
                'fr' => 'Conditions d’utilisation', 'de' => 'Nutzungsbedingungen',
                'es' => 'Condiciones de uso', 'it' => 'Termini di utilizzo',
            ],
            'return' => [
                'tr' => 'İade ve Değişim Koşulları', 'en' => 'Return and Exchange Conditions',
                'ar' => 'شروط الإرجاع والاستبدال', 'ru' => 'Условия возврата и обмена',
                'fa' => 'شرایط مرجوعی و تعویض', 'uk' => 'Умови повернення та обміну',
                'fr' => 'Conditions de retour et d’échange', 'de' => 'Rückgabe- und Umtauschbedingungen',
                'es' => 'Condiciones de devolución y cambio', 'it' => 'Condizioni di reso e cambio',
            ],
            'kvkk' => [
                'tr' => 'KVKK Aydınlatma Metni', 'en' => 'Personal Data Protection Notice',
                'ar' => 'إشعار حماية البيانات الشخصية', 'ru' => 'Уведомление о защите персональных данных',
                'fa' => 'اطلاعیه حفاظت از داده‌های شخصی', 'uk' => 'Повідомлення про захист персональних даних',
                'fr' => 'Avis de protection des données personnelles',
                'de' => 'Hinweis zum Schutz personenbezogener Daten',
                'es' => 'Aviso de protección de datos personales',
                'it' => 'Informativa sulla protezione dei dati personali',
            ],
            'whatsapp' => array_fill_keys(config('storefront.locales'), 'WhatsApp'),
            'instagram' => array_fill_keys(config('storefront.locales'), 'Instagram'),
        ];
    }

    private function seedSiteSettings(): void
    {
        $perLocale = [
            'tr' => [
                'siteName' => 'Merter Giyim',
                'footerBrand' => 'Merter Giyim',
                'footerDescription' => '<p>Merter’den Türkiye’nin her noktasına toptan ve perakende kadın giyim.</p>',
                'footerInfoTitle' => 'Bilgilendirmeler',
                'footerAddress' => 'MERTER / İSTANBUL',
                'copyright' => '© '.date('Y').' Merter Giyim. Tüm hakları saklıdır.',
                'contactTitle' => 'İletişim',
                'contactDescription' => '<p>Merter Giyim showroom ve toptan sipariş süreçleri için bizimle iletişime geçebilirsin.</p>',
                'contactAddress' => 'Mehmet Nesih Özmen Mahallesi, Savaş Caddesi, Vardarlı Çarşı, No: 21, Kat: 2, Dükkan: 37, Merter, İstanbul',
            ],
            'en' => [
                'siteName' => 'Merter Clothing',
                'footerBrand' => 'Merter Clothing',
                'footerDescription' => '<p>Wholesale and retail women’s clothing shipped worldwide from Merter, Istanbul.</p>',
                'footerInfoTitle' => 'Information',
                'footerAddress' => 'MERTER / ISTANBUL',
                'copyright' => '© '.date('Y').' Merter Clothing. All rights reserved.',
                'contactTitle' => 'Contact',
                'contactDescription' => '<p>Contact us for the Merter Giyim showroom and wholesale ordering process.</p>',
                'contactAddress' => 'Mehmet Nesih Özmen District, Savaş Avenue, Vardarlı Bazaar, No: 21, Floor: 2, Shop: 37, Merter, Istanbul',
            ],
        ];

        foreach (config('storefront.locales') as $locale) {
            $perLocale[$locale] ??= $perLocale['en'];
        }

        $footerInfoTitles = [
            'tr' => 'Bilgilendirmeler', 'en' => 'Information', 'ar' => 'معلومات',
            'ru' => 'Информация', 'fa' => 'اطلاعات', 'uk' => 'Інформація',
            'fr' => 'Informations', 'de' => 'Informationen', 'es' => 'Información',
            'it' => 'Informazioni',
        ];

        foreach ($footerInfoTitles as $locale => $title) {
            $perLocale[$locale]['footerInfoTitle'] = $title;
        }

        $homeFields = [
            'homeCategoryTitle' => 'categories',
            'homeAllCategoriesLabel' => 'allCategories',
            'homeCollectionLabel' => 'collection',
            'homeFeaturedTitle' => 'featuredProducts',
            'homeOrderNotice' => 'orderNotice',
            'homeEmptyTitle' => 'empty',
            'homeEmptyDescription' => 'emptyDescription',
            'homeFilterEmptyTitle' => 'filterEmpty',
            'homeFilterEmptyDescription' => 'filterEmptyDescription',
            'homeShowAllProductsLabel' => 'showAllProducts',
        ];

        foreach (config('storefront.locales') as $locale) {
            $dictionary = json_decode((string) file_get_contents(lang_path('storefront/'.$locale.'.json')), true);

            foreach ($homeFields as $settingKey => $dictionaryKey) {
                $perLocale[$locale][$settingKey] = (string) data_get($dictionary, 'home.'.$dictionaryKey, '');
            }

            $perLocale[$locale]['homeSeoTitle'] = (string) data_get($dictionary, 'meta.title', '');
            $perLocale[$locale]['homeSeoDescription'] = (string) data_get($dictionary, 'meta.description', '');
            $perLocale[$locale]['homeSeoKeywords'] = (string) data_get($dictionary, 'meta.keywords', '');
        }

        $perLocale['general'] = [
            'siteLogo' => null,
            'homeProductLimit' => 12,
            'homeSeoShareImage' => null,
            'whatsappNumber' => (string) config('storefront.whatsapp_number'),
            'contactPhone' => '0532 325 97 88',
            'contactEmail' => 'info@mertertextile.com',
            'socialLinks' => [
                ['platform' => 'instagram', 'label' => null, 'url' => 'https://www.instagram.com/'],
                ['platform' => 'facebook', 'label' => null, 'url' => 'https://www.facebook.com/'],
            ],
            'googleMapsIframe' => '',
        ];

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
