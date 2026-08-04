<?php

namespace App\Http\Controllers;

use App\Support\Storefront;

use App\Models\Media;
use App\Models\MediaPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MultimediaController extends Controller
{
    public function __invoke(): View
    {
        $locale = app()->getLocale();
        return view('storefront.multimedia', [
            'mediaPosts' => $this->mediaPosts(),
            'canonicalPath' => Storefront::localePath($locale, '/multimedya'),
            'alternatePath' => fn (string $code) => Storefront::localePath($code, '/multimedya'),
        ]);
    }

    /**
     * Albüm SQL'i henüz çalıştırılmamış ortamlarda eski tekil medya kayıtları
     * vitrini çalıştırmaya devam eder. Tablolar oluşunca yeni ilişki otomatik
     * olarak devreye girer.
     */
    private function mediaPosts(): Collection
    {
        if (Schema::hasTable('media_posts') && Schema::hasTable('media_files')) {
            return MediaPost::query()
                ->where('active', true)
                ->whereHas('files')
                ->with('files')
                ->orderBy('sort_order')
                ->get();
        }

        return Media::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Media $media): object => (object) [
                'id' => $media->id,
                'title' => $media->title,
                'description' => $media->caption,
                'active' => $media->active,
                'sort_order' => $media->sort_order,
                'files' => collect([(object) [
                    'id' => $media->id,
                    'type' => $media->type,
                    'file_path' => $media->file_path,
                    'alt' => $media->alt,
                    'sort_order' => 0,
                ]]),
            ]);
    }
}
