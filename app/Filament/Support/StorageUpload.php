<?php

namespace App\Filament\Support;

use App\Services\ImageUploader;
use App\Support\Storefront;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Supabase Storage'a (veya lokal yedek diske) yükleme yapan ortak alan.
 *
 * Kaydedilen değer bucket'a göreli yoldur; vitrindeki publicStorageUrl
 * karşılığı Storefront::storageUrl bu yolu doğrudan kullanır.
 */
class StorageUpload
{
    /**
     * @param  string  $bucketKey  'products' | 'site'
     * @param  (callable(mixed): string)|string  $directory  Bucket içindeki klasör
     */
    public static function image(string $name, string $bucketKey, callable|string $directory = ''): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->imageEditor()
            ->maxSize(12 * 1024)
            ->helperText('Yükleme sırasında en uzun kenar '.config('storefront.upload.max_size').'px olacak şekilde küçültülüp WebP\'e çevrilir.')
            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, $livewire) use ($bucketKey, $directory) {
                $folder = is_callable($directory) ? $directory($livewire) : $directory;

                return app(ImageUploader::class)->store($file, $bucketKey, $folder);
            })
            ->getUploadedFileUsing(fn (string $file): array => [
                'name' => basename($file),
                'url' => Storefront::storageUrl($bucketKey, $file),
            ]);
    }
}
