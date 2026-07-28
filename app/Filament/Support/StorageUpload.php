<?php

namespace App\Filament\Support;

use App\Services\ImageUploader;
use App\Support\Storefront;
use App\Support\UploadTarget;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public static function file(string $name, string $bucketKey, string $directory = 'media'): FileUpload
    {
        return FileUpload::make($name)
            ->maxSize(50 * 1024)
            ->acceptedFileTypes([
                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                'video/mp4', 'video/webm',
                'application/pdf',
            ])
            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) use ($bucketKey, $directory): string {
                $extension = $file->getClientOriginalExtension();
                $filename = now()->getTimestampMs().'-'.Str::random(6).'-'.Str::slug(
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                ).($extension ? '.'.$extension : '');
                $path = trim($directory, '/').'/'.$filename;

                Storage::disk(UploadTarget::disk($bucketKey))->putFileAs(
                    UploadTarget::pathPrefix($bucketKey).trim($directory, '/'),
                    $file,
                    $filename,
                    ['visibility' => 'public'],
                );

                return $path;
            })
            ->getUploadedFileUsing(fn (string $file): array => [
                'name' => basename($file),
                'url' => Storefront::storageUrl($bucketKey, $file),
            ]);
    }
}
