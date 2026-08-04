<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\BlogPost;
use App\Models\Color;
use App\Models\ContentPage;
use App\Models\Currency;
use App\Models\HeroSlide;
use App\Models\Language;
use App\Models\MediaFile;
use App\Models\MediaPost;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SiteLink;
use App\Models\SiteSetting;
use App\Models\Size;
use App\Observers\ProductImageObserver;
use App\Observers\StorefrontCacheObserver;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Vitrin verisini besleyen modeller; her değişiklikte storefront cache'i
     * temizlenir.
     *
     * @var array<int, class-string>
     */
    private const CACHED_MODELS = [
        Product::class,
        ProductImage::class,
        ProductVariant::class,
        Category::class,
        Size::class,
        Color::class,
        Currency::class,
        Language::class,
        HeroSlide::class,
        SiteLink::class,
        SiteSetting::class,
        ContentPage::class,
        BlogPost::class,
        MediaPost::class,
        MediaFile::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach (self::CACHED_MODELS as $model) {
            $model::observe(StorefrontCacheObserver::class);
        }

        ProductImage::observe(ProductImageObserver::class);

        // Panelin varsayılan CRUD akışı modaldir: yeni/düzenle/sil işlemleri
        // listenin üzerinde açılır. Burada YALNIZCA modalın görünümünü ayarlıyoruz.
        //
        // ->modal() ÇAĞIRMIYORUZ bilerek: Create/Edit action'ları form şeması
        // olduğu için zaten varsayılan olarak modal açar. ->modal() ise hasModal'ı
        // zorla true yapıp Filament'in "->url() varsa tam sayfaya git" kararını
        // ezer (CanOpenUrl::getUrl, hasModal true iken url'i yok sayıp null döner).
        // Böylece bir kaynak ->url() ile tam sayfa düzenlemeyi seçtiğinde sistem
        // kendiliğinden doğru davranır; modal isteyen kaynak url vermez, o kadar.
        CreateAction::configureUsing(fn (CreateAction $action) => $action
            ->modalWidth(Width::FourExtraLarge)
            ->createAnother(false));

        EditAction::configureUsing(fn (EditAction $action) => $action
            ->modalWidth(Width::FourExtraLarge)
            ->button()
            ->label('Düzenle'));

        DeleteAction::configureUsing(fn (DeleteAction $action) => $action
            ->modal()
            ->modalWidth(Width::Large)
            ->button()
            ->label('Sil'));

        DeleteBulkAction::configureUsing(fn (DeleteBulkAction $action) => $action
            ->modal()
            ->modalWidth(Width::Large));
    }
}
