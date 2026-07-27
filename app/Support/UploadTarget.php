<?php

namespace App\Support;

/**
 * Görsel yüklemelerinin hangi diske gideceğine karar verir.
 *
 * S3 anahtarları henüz yokken FILESYSTEM_SUPABASE_DISK boş bırakılır ve
 * yüklemeler lokal yedek diske düşer; kaydedilen storage_path değeri
 * her iki durumda da aynı formatta (bucket'a göreli yol) kalır.
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
        return self::usesSupabase() ? 'supabase_'.$bucketKey : 'local_supabase_stub';
    }

    public static function bucket(string $bucketKey): string
    {
        return config('storefront.buckets.'.$bucketKey, $bucketKey);
    }

    /**
     * Yedek diskte dosyalar bucket adıyla başlayan bir klasöre yazılır ki
     * storage_path değeri canlıdaki ile aynı kalsın.
     */
    public static function pathPrefix(string $bucketKey): string
    {
        return self::usesSupabase() ? '' : self::bucket($bucketKey).'/';
    }
}
