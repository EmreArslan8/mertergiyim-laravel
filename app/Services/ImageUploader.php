<?php

namespace App\Services;

use App\Support\UploadTarget;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Kaynak projedeki lib/admin/compress-image.ts karşılığı: yükleme öncesi
 * görseli en uzun kenarı ~1600px olacak şekilde küçültüp WebP'e çevirir.
 */
class ImageUploader
{
    /**
     * @param  string  $bucketKey  'products' | 'site'
     * @param  string  $directory  Bucket içindeki klasör ('' olabilir)
     * @return string Bucket'a göreli storage_path
     */
    public function store(UploadedFile $file, string $bucketKey, string $directory = ''): string
    {
        $directory = trim($directory, '/');
        $name = pathinfo($file->getClientOriginalName() ?: 'image', PATHINFO_FILENAME);
        $slug = Str::slug($name) ?: 'image';
        $filename = now()->getTimestampMs().'-'.Str::random(6).'-'.$slug;

        [$contents, $extension] = $this->optimize($file);

        $path = ($directory !== '' ? $directory.'/' : '').$filename.'.'.$extension;

        Storage::disk(UploadTarget::disk($bucketKey))
            ->put(UploadTarget::pathPrefix($bucketKey).$path, $contents, 'public');

        return $path;
    }

    public function delete(string $bucketKey, ?string $path): void
    {
        if (! $path || str_starts_with($path, 'http')) {
            return;
        }

        rescue(fn () => Storage::disk(UploadTarget::disk($bucketKey))
            ->delete(UploadTarget::pathPrefix($bucketKey).ltrim($path, '/')), report: false);
    }

    /**
     * @return array{0: string, 1: string} [içerik, uzantı]
     */
    private function optimize(UploadedFile $file): array
    {
        $raw = $file->get();

        try {
            $manager = new ImageManager(new Driver);
            $image = $manager->decodeBinary($raw);

            $max = (int) config('storefront.upload.max_size', 1600);
            if ($image->width() > $max || $image->height() > $max) {
                $image->scaleDown($max, $max);
            }

            $encoded = (string) $image->encode(new WebpEncoder(quality: (int) config('storefront.upload.quality', 80)));

            // Sıkıştırma dosyayı büyüttüyse orijinali koru (kaynaktaki davranış).
            return strlen($encoded) < strlen($raw)
                ? [$encoded, 'webp']
                : [$raw, $file->getClientOriginalExtension() ?: 'jpg'];
        } catch (\Throwable) {
            return [$raw, $file->getClientOriginalExtension() ?: 'jpg'];
        }
    }
}
