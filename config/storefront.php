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
    ],

    // Görsel yoksa kullanılacak yer tutucu (boş bırakılırsa görsel basılmaz).
    'placeholder_image' => env('STOREFRONT_PLACEHOLDER_IMAGE', ''),

    'whatsapp_number' => env('STOREFRONT_WHATSAPP_NUMBER', '905323259788'),

    // Sipariş oluşunca mağazaya WhatsApp bildirimi (Meta Cloud API).
    // Kimlik bilgileri boşken bildirim sessizce atlanır, sipariş normal kaydedilir.
    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'token' => env('WHATSAPP_ACCESS_TOKEN'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

        // Bildirimin gideceği numara; boşsa yukarıdaki mağaza numarası kullanılır.
        'recipient' => env('WHATSAPP_NOTIFY_NUMBER'),

        // 'template': onaylı şablonla gönderir (24 saat kuralı nedeniyle
        // varsayılan ve tek güvenilir yol). 'text': düz metin gönderir, yalnızca
        // mağaza son 24 saat içinde yazmışsa çalışır.
        'mode' => env('WHATSAPP_MESSAGE_MODE', 'template'),
        'template' => env('WHATSAPP_TEMPLATE_NAME', 'yeni_siparis'),
        'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'tr'),
    ],

    'site_url' => env('STOREFRONT_SITE_URL', env('APP_URL', 'http://localhost')),

    // Sorgu cache süresi (saniye). Kaynak Next.js projesindeki revalidate = 3600.
    'cache_ttl' => (int) env('STOREFRONT_CACHE_TTL', 3600),

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
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        // Kaynak app/api/translate/route.ts ile birebir aynı liste ve sıra.
        'languages' => ['en', 'ar', 'ru', 'fa', 'uk', 'fr', 'de', 'es', 'it'],
    ],

    'exchange' => [
        'tcmb_endpoint' => env('TCMB_EXCHANGE_RATE_ENDPOINT', 'https://www.tcmb.gov.tr/kurlar/today.xml'),
        'fallback_endpoint' => env('EXCHANGE_RATE_FALLBACK_ENDPOINT', 'https://api.frankfurter.dev'),
    ],
];
