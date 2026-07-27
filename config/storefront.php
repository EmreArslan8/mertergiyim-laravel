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
];
