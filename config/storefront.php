<?php

return [
    // Vitrinin desteklediği diller. Sıra dil çubuğu/hreflang çıktısını etkilemez;
    // gösterim sırası languages tablosundaki sort_order'dan gelir.
    'locales' => ['tr', 'en', 'ar', 'ru', 'fa', 'uk', 'fr', 'de', 'es', 'it'],

    'default_locale' => 'tr',

    'rtl_locales' => ['ar', 'fa'],

    // Supabase Storage public base URL. Bucket + path bunun altına eklenir.
    'storage_url' => env('SUPABASE_STORAGE_URL', 'https://whcylakuagonefgjdqhx.supabase.co/storage/v1/object/public'),

    'buckets' => [
        'products' => 'product-images',
        'site' => 'site-media',
    ],

    // Görsel yoksa kullanılacak yer tutucu (boş bırakılırsa görsel basılmaz).
    'placeholder_image' => env('STOREFRONT_PLACEHOLDER_IMAGE', ''),

    'whatsapp_number' => env('STOREFRONT_WHATSAPP_NUMBER', '905323259788'),

    'site_url' => env('STOREFRONT_SITE_URL', 'https://www.mertergiyim.com'),

    // Sorgu cache süresi (saniye). Kaynak Next.js projesindeki revalidate = 3600.
    'cache_ttl' => (int) env('STOREFRONT_CACHE_TTL', 3600),

    // Panelden yapılan görsel yüklemeleri.
    'upload' => [
        // 'supabase' -> S3 uyumlu Supabase Storage, aksi halde lokal yedek disk.
        'target' => env('FILESYSTEM_SUPABASE_DISK', 'local_supabase_stub'),

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
];
