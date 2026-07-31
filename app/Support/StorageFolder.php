<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Yüklenen dosyayı ait olduğu kaydın klasörüne taşır: albüm dosyaları
 * site-media/media/<albüm_id>/, ürün görselleri product-images/<ürün_id>/.
 *
 * Neden model tarafında: panel formlarında dosya, kayıt henüz oluşmadan
 * (dolayısıyla id yokken) yükleniyor. Repeater satırları ise ancak sahibi
 * kaydedildikten sonra yazılıyor; taşımanın id'yi kesin bildiği tek an bu.
 */
class StorageFolder
{
    /**
     * @param  string  $column  Bucket'a göreli yolu tutan kolon
     * @param  string  $bucketKey  'products' | 'site'
     * @param  string  $prefix  Bucket içindeki üst klasör ('media' gibi, boş olabilir)
     */
    public static function relocate(Model $record, string $column, string $bucketKey, string $prefix, ?string $ownerId): void
    {
        $path = ltrim((string) $record->getAttribute($column), '/');

        // Dış bağlantılar ve boş değerler taşınmaz.
        if ($path === '' || str_starts_with($path, 'http') || ! $ownerId) {
            return;
        }

        $target = trim($prefix.'/'.$ownerId, '/');

        if (str_starts_with($path, $target.'/')) {
            return;
        }

        $disk = Storage::disk(UploadTarget::disk($bucketKey));
        $source = UploadTarget::pathPrefix($bucketKey).$path;
        $newPath = $target.'/'.basename($path);
        $destination = UploadTarget::pathPrefix($bucketKey).$newPath;

        // Eski Supabase kayıtları yerel diskte bulunmaz; yolu olduğu gibi bırak.
        if (! $disk->exists($source) || $disk->exists($destination)) {
            return;
        }

        if (! rescue(fn (): bool => $disk->move($source, $destination), false, report: false)) {
            return;
        }

        // saveQuietly: aynı olayı tekrar tetiklemeden yolu güncelle.
        $record->forceFill([$column => $newPath])->saveQuietly();
    }
}
