<?php

namespace App\Support;

/**
 * Görsel yüklemelerinin hangi diske gideceğine karar verir.
 *
 * Alwaysdata'da dosyalar public_media diskine, isteğe bağlı Supabase
 * kurulumunda S3 uyumlu diske yazılır. Veritabanında her iki hedef için de
 * yalnızca ilgili klasöre göreli dosya yolu saklanır.
 */
class UploadTarget
{
    public static function usesSupabase(): bool
    {
        return config('storefront.upload.target') === 'supabase';
    }

    /**
     * @param  string  $bucketKey  'products' | 'site'
     */
    public static function disk(string $bucketKey): string
    {
        return self::usesSupabase() ? 'supabase_'.$bucketKey : 'public_media_'.$bucketKey;
    }

    public static function bucket(string $bucketKey): string
    {
        return config('storefront.buckets.'.$bucketKey, $bucketKey);
    }

    /**
     * Her iki hedefte de disk zaten bucket klasörüne kök saldığı için yola
     * ayrıca önek eklenmez. Böylece veritabanındaki yol (ör. "xxx.webp")
     * diskteki konumla birebir aynı olur; panelin yükleme alanı dosyayı
     * bulabilir, dosya adı Supabase kurulumuyla da uyumlu kalır.
     */
    public static function pathPrefix(string $bucketKey): string
    {
        return '';
    }
}
