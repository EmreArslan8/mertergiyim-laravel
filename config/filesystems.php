<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /*
        | Alwaysdata/Plesk benzeri sunucularda ürün ve site dosyaları.
        | public/storage sembolik bağlantısı bu diski doğrudan yayınlar.
        */
        'public_media' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
        | Supabase Storage, S3 uyumlu uç nokta üzerinden kullanılır.
        | Anahtarlar: Supabase Dashboard -> Storage -> S3 access keys.
        | Her bucket için ayrı disk gerekir (Flysystem S3 tek bucket'a bağlanır).
        */
        'supabase_products' => [
            'driver' => 's3',
            'key' => env('SUPABASE_S3_ACCESS_KEY_ID'),
            'secret' => env('SUPABASE_S3_SECRET_ACCESS_KEY'),
            'region' => env('SUPABASE_S3_REGION', 'eu-central-1'),
            'bucket' => env('SUPABASE_BUCKET_PRODUCTS', 'product-images'),
            'endpoint' => env('SUPABASE_S3_ENDPOINT', 'https://whcylakuagonefgjdqhx.supabase.co/storage/v1/s3'),
            'url' => env('SUPABASE_STORAGE_URL').'/'.env('SUPABASE_BUCKET_PRODUCTS', 'product-images'),
            'use_path_style_endpoint' => true,
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        'supabase_site' => [
            'driver' => 's3',
            'key' => env('SUPABASE_S3_ACCESS_KEY_ID'),
            'secret' => env('SUPABASE_S3_SECRET_ACCESS_KEY'),
            'region' => env('SUPABASE_S3_REGION', 'eu-central-1'),
            'bucket' => env('SUPABASE_BUCKET_SITE', 'site-media'),
            'endpoint' => env('SUPABASE_S3_ENDPOINT', 'https://whcylakuagonefgjdqhx.supabase.co/storage/v1/s3'),
            'url' => env('SUPABASE_STORAGE_URL').'/'.env('SUPABASE_BUCKET_SITE', 'site-media'),
            'use_path_style_endpoint' => true,
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        /*
        | S3 anahtarları gelmeden lokal test için yedek disk. Dosyalar
        | storage/app/public/<bucket>/<path> altına yazılır; böylece
        | kaydedilen storage_path formatı canlıdakiyle birebir aynı kalır.
        */
        // Panel yükleme alanı, veritabanındaki yolu (ör. "xxx.webp") diskte
        // doğrudan arıyor. public_media kökü storage/app/public olduğu için
        // "product-images/xxx.webp" bulunamıyor ve alan boşaltılıyordu. Bu
        // diskler doğrudan bucket klasörüne kök salar, yol birebir eşleşir.
        'public_media_products' => [
            'driver' => 'local',
            'root' => storage_path('app/public/'.env('STOREFRONT_PRODUCTS_DIRECTORY', 'product-images')),
            'url' => env('APP_URL').'/storage/'.env('STOREFRONT_PRODUCTS_DIRECTORY', 'product-images'),
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        'public_media_site' => [
            'driver' => 'local',
            'root' => storage_path('app/public/'.env('STOREFRONT_SITE_DIRECTORY', 'site-media')),
            'url' => env('APP_URL').'/storage/'.env('STOREFRONT_SITE_DIRECTORY', 'site-media'),
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        'local_supabase_stub' => [
            'driver' => 'local',
            'root' => storage_path('app/public/supabase'),
            'url' => env('APP_URL').'/storage/supabase',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
