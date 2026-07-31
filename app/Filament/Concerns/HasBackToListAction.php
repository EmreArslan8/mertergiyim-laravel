<?php

namespace App\Filament\Concerns;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

/**
 * Kayıt sayfalarında başlığın ÜSTÜNDE, solda duran "listeye dön" bağlantısı.
 * (Breadcrumb'ın olması gereken yer; Shopify/Stripe/Linear da geri hareketini
 * başlığın üstüne koyar. Başlığın altı alt-başlık/açıklama alanıdır.)
 *
 * Tema breadcrumb'ları gizlediği için (merter-admin.css `.fi-breadcrumbs`)
 * düzenleme/oluşturma sayfasında geri dönmenin görünür bir yolu kalmıyordu.
 * Başlık aksiyonları sağda hizalandığı için geri butonu oraya konamaz: geri
 * hareketi soldan, yıkıcı aksiyonlar (Sil) sağdan gider.
 *
 * Kayıt `booted` içinde yapılır; `mount` yalnızca ilk isteklerde çalıştığı için
 * Livewire güncellemelerinden sonra bağlantı kaybolurdu.
 */
trait HasBackToListAction
{
    public function bootedHasBackToListAction(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_HEADER_HEADING_BEFORE,
            fn (): string => view('filament.back-to-list', [
                'url' => static::getResource()::getUrl('index'),
                'label' => $this->backToListLabel(),
            ])->render(),
            scopes: static::class,
        );
    }

    protected function backToListLabel(): string
    {
        return 'Listeye dön';
    }
}
