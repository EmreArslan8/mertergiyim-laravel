<?php

return [
    // Vitrinin desteklediği diller. Sıra dil çubuğu/hreflang çıktısını etkilemez;
    // gösterim sırası languages tablosundaki sort_order'dan gelir.
    'locales' => ['tr', 'en', 'ar', 'ru', 'fa', 'uk', 'fr', 'de', 'es', 'it'],

    'default_locale' => 'tr',

    // Veritabanında henüz Site Ayarları kaydı yokken kullanılan nötr marka adı.
    'brand_name' => env('STOREFRONT_BRAND_NAME', 'Mağaza'),

    'rtl_locales' => ['ar', 'fa'],

    // Yalnızca Supabase seçilirse kullanılır; Alwaysdata'da /storage üretilir.
    'storage_url' => env('SUPABASE_STORAGE_URL', ''),

    'buckets' => [
        // Eski Supabase kayıtlarıyla geriye uyumlu klasör adları. Alwaysdata
        // kurulumunda istenirse env üzerinden products/site yapılabilir.
        'products' => env('STOREFRONT_PRODUCTS_DIRECTORY', 'product-images'),
        'site' => env('STOREFRONT_SITE_DIRECTORY', 'site-media'),
        // Telegram kanallarından indirilen ham ürün görselleri; kataloğa
        // aktarılana kadar products klasörüne karışmasınlar diye ayrı.
        'telegram' => env('STOREFRONT_TELEGRAM_DIRECTORY', 'telegram-images'),
    ],

    // Görsel yoksa kullanılacak yer tutucu (boş bırakılırsa görsel basılmaz).
    'placeholder_image' => env('STOREFRONT_PLACEHOLDER_IMAGE', ''),

    'whatsapp_number' => env('STOREFRONT_WHATSAPP_NUMBER', '905323259788'),

    // Sipariş oluşunca mağazaya Telegram bildirimi (Telegram Bot API).
    // Kimlik bilgileri boşken bildirim sessizce atlanır, sipariş normal kaydedilir.
    // Kurulum: @BotFather'dan bot token alın; bildirimlerin gideceği kişi/grubun
    // chat id'sini env'e (TELEGRAM_CHAT_ID) veya panel > Site Ayarları'na girin.
    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),

        // Mesajın gideceği chat id (kişi veya grup). Grupta '-100...' ile başlar.
        // Boşsa panelden girilen "Sipariş bildirimi Telegram Chat ID" kullanılır.
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    /*
    |----------------------------------------------------------------------
    | Telegram istemcisi (ürün çekimi — MTProto)
    |----------------------------------------------------------------------
    |
    | api_id/api_hash Telegram'da KULLANICIYI değil UYGULAMAYI temsil eder:
    | tek bir kimlik altında farklı numaralar giriş yapabilir. Bu yüzden
    | anahtarlar burada, kurulum genelinde tanımlanır; panele numarasını
    | giren kişinin my.telegram.org'a uğraması gerekmez, yalnızca telefon
    | numarasını yazıp gelen kodu girer.
    |
    | Kurulum: my.telegram.org → API development tools → uygulama oluştur.
    | Ayrı bir kimlikle çalışması gereken hesap olursa panelde kayıt bazında
    | girilen değer buradakini geçersiz kılar.
    |
    */
    'telegram_client' => [
        'api_id' => env('TELEGRAM_API_ID'),
        'api_hash' => env('TELEGRAM_API_HASH'),
    ],

    'site_url' => env('STOREFRONT_SITE_URL', env('APP_URL', 'http://localhost')),

    // Sorgu cache süresi (saniye). Kaynak Next.js projesindeki revalidate = 3600.
    'cache_ttl' => (int) env('STOREFRONT_CACHE_TTL', 3600),

    /*
    |----------------------------------------------------------------------
    | Paket (seri) varsayılanları
    |----------------------------------------------------------------------
    |
    | Toptan satışta ürün paket halinde satılır; paket içindeki beden
    | dağılımı sabit kalıplardan gelir. Buradaki değerler yalnızca
    | VARSAYILAN: panelde beden adetleri her zaman elle değiştirilebilir,
    | değiştirildiği anda otomatik dağıtım devreden çıkar ("Otomatik dağıt"
    | bağlantısı kalıbı geri getirir).
    |
    | 'templates' -> beden sayısı => [adetler]. Kalıbın toplamı paket
    | adediyle eşleşmiyorsa kalıp atlanır ve hesaplanmış dağıtım kullanılır
    | (taban pay + kalanı ortadan küçük bedenlere doğru).
    |
    */
    'pack' => [
        // Yeni ürün açılırken önerilen paket adedi.
        'default_size' => (int) env('STOREFRONT_PACK_DEFAULT_SIZE', 5),

        // 5'li seride sahadaki kalıplar.
        'templates' => [
            3 => [2, 2, 1],
            4 => [1, 2, 1, 1],
        ],

        // Panelde tablonun üstünde çıkan hazır seri butonları. Basınca dağılım
        // o adede göre kurulur; ayrı bir "paket adedi" alanı yok, paket adedi
        // her zaman tablodaki adetlerin toplamıdır.
        'presets' => [5, 6, 8, 10],
    ],

    // Panelden yapılan görsel yüklemeleri.
    'upload' => [
        // Alwaysdata'da 'local'; gerektiğinde geriye uyumlu olarak 'supabase'.
        'target' => env('STOREFRONT_UPLOAD_DISK', env('FILESYSTEM_SUPABASE_DISK', 'local')),

        // Yükleme öncesi yeniden boyutlandırma (kaynak compress-image.ts ile aynı).
        'max_size' => (int) env('STOREFRONT_UPLOAD_MAX_SIZE', 1600),
        'quality' => (int) env('STOREFRONT_UPLOAD_QUALITY', 80),
    ],

    // Gemini otomatik çeviri.
    'translation' => [
        'api_key' => env('GEMINI_API_KEY'),

        // Barındırma IP'si Gemini tarafından reddediliyorsa (User location is
        // not supported) vekil adres; boşsa doğrudan Google'a gidilir.
        'base_url' => env('GEMINI_BASE_URL', ''),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        // Kaynak app/api/translate/route.ts ile birebir aynı liste ve sıra.
        'languages' => ['en', 'ar', 'ru', 'fa', 'uk', 'fr', 'de', 'es', 'it'],
    ],

    'exchange' => [
        'tcmb_endpoint' => env('TCMB_EXCHANGE_RATE_ENDPOINT', 'https://www.tcmb.gov.tr/kurlar/today.xml'),
        'fallback_endpoint' => env('EXCHANGE_RATE_FALLBACK_ENDPOINT', 'https://api.frankfurter.dev'),
    ],
];
