<?php

namespace App\Filament\Resources\Homepage\Schemas;

use App\Filament\Resources\HeroSlides\HeroSlideResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\Multilingual;
use App\Filament\Support\StorageUpload;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class HomepageForm
{
    /** Türkçe girilir; aktif diğer dillere kaydederken otomatik çevrilir. */
    public const FIELDS = [
        'homeCategoryTitle' => 'Kategori bölümü başlığı',
        'homeAllCategoriesLabel' => 'Tüm kategoriler etiketi',
        'homeCollectionLabel' => 'Koleksiyon üst başlığı',
        'homeFeaturedTitle' => 'Ürün bölümü başlığı',
        'homeOrderNotice' => 'Ürün bölümü açıklaması',
        'homeEmptyTitle' => 'Ürün yok başlığı',
        'homeEmptyDescription' => 'Ürün yok açıklaması',
        'homeFilterEmptyTitle' => 'Filtre sonucu boş başlığı',
        'homeFilterEmptyDescription' => 'Filtre sonucu boş açıklaması',
        'homeShowAllProductsLabel' => 'Tüm ürünleri göster düğmesi',
        'homeSeoTitle' => 'Ana sayfa SEO başlığı',
        'homeSeoDescription' => 'Ana sayfa SEO açıklaması',
        'homeSeoKeywords' => 'Ana sayfa SEO anahtar kelimeleri',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Ana sayfa bölümleri')
                ->extraAttributes(['class' => 'merter-homepage-tabs'])
                ->columnSpanFull()
                ->persistTabInQueryString('section')
                ->tabs([
                    Tab::make('Hero')
                        ->icon(Heroicon::OutlinedPhoto)
                        ->schema([
                            Section::make('Hero ve slider alanı')
                                ->description('Ana sayfanın büyük görsellerini, metinlerini, sırasını ve yayın durumunu Slider ekranından yönetin.')
                                ->schema([
                                    Actions::make([
                                        Action::make('manageHeroSlides')
                                            ->label('Hero slaytlarını yönet')
                                            ->icon(Heroicon::OutlinedPhoto)
                                            ->url(fn (): string => HeroSlideResource::getUrl('index')),
                                    ]),
                                ]),
                        ]),

                    Tab::make('Ürün Bölümü')
                        ->icon(Heroicon::OutlinedShoppingBag)
                        ->schema([
                            Section::make('Vitrin ürünleri')
                                ->description('Gösterilecek ürün adedini belirleyin. Aktif ürünler en yeniden en eskiye sıralanır; sınırı aşan en eski ürünler ana sayfada gösterilmez.')
                                ->columns(['default' => 1, 'md' => 2])
                                ->schema([
                                    TextInput::make('value.general.homeProductLimit')
                                        ->label('Ana sayfada gösterilecek ürün adedi')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(1)
                                        ->maxValue(100)
                                        ->default(12)
                                        ->required(),
                                    Actions::make([
                                        Action::make('manageProducts')
                                            ->label('Ürünleri yönet')
                                            ->icon(Heroicon::OutlinedShoppingBag)
                                            ->color('gray')
                                            ->url(fn (): string => ProductResource::getUrl('index')),
                                    ])->alignEnd(),
                                ]),
                            Section::make('Bölüm metinleri')
                                ->description('Türkçe metni yazın; diğer aktif diller kaydederken otomatik hazırlanır.')
                                ->columns(['default' => 1, 'md' => 2])
                                ->schema([
                                    TextInput::make('value.tr.homeCollectionLabel')
                                        ->label(self::FIELDS['homeCollectionLabel'].' (Türkçe)'),
                                    TextInput::make('value.tr.homeFeaturedTitle')
                                        ->label(self::FIELDS['homeFeaturedTitle'].' (Türkçe)'),
                                    Multilingual::richEditor('value.tr.homeOrderNotice', self::FIELDS['homeOrderNotice'].' (Türkçe)')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('Kategoriler')
                        ->icon(Heroicon::OutlinedRectangleStack)
                        ->schema([
                            Section::make('Kategori filtreleri')
                                ->description('Ürünlerin üstündeki kategori filtre alanında gösterilen metinler.')
                                ->columns(['default' => 1, 'md' => 2])
                                ->schema([
                                    TextInput::make('value.tr.homeCategoryTitle')
                                        ->label(self::FIELDS['homeCategoryTitle'].' (Türkçe)'),
                                    TextInput::make('value.tr.homeAllCategoriesLabel')
                                        ->label(self::FIELDS['homeAllCategoriesLabel'].' (Türkçe)'),
                                ]),
                        ]),

                    Tab::make('Boş Durumlar')
                        ->icon(Heroicon::OutlinedExclamationCircle)
                        ->schema([
                            Section::make('Ürün bulunamadığında')
                                ->description('Henüz ürün yokken veya seçilen kategoride sonuç bulunmadığında ziyaretçiye gösterilir.')
                                ->columns(['default' => 1, 'md' => 2])
                                ->schema([
                                    TextInput::make('value.tr.homeEmptyTitle')
                                        ->label(self::FIELDS['homeEmptyTitle'].' (Türkçe)'),
                                    TextInput::make('value.tr.homeFilterEmptyTitle')
                                        ->label(self::FIELDS['homeFilterEmptyTitle'].' (Türkçe)'),
                                    Multilingual::richEditor('value.tr.homeEmptyDescription', self::FIELDS['homeEmptyDescription'].' (Türkçe)'),
                                    Multilingual::richEditor('value.tr.homeFilterEmptyDescription', self::FIELDS['homeFilterEmptyDescription'].' (Türkçe)'),
                                    TextInput::make('value.tr.homeShowAllProductsLabel')
                                        ->label(self::FIELDS['homeShowAllProductsLabel'].' (Türkçe)')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('SEO')
                        ->icon(Heroicon::OutlinedMagnifyingGlass)
                        ->schema([
                            Section::make('Ana sayfa arama görünümü')
                                ->description('Bu alanlar yalnızca ana sayfada kullanılır. Boş kalırsa Site Ayarları içindeki varsayılan SEO bilgileri devreye girer.')
                                ->schema([
                                    TextInput::make('value.tr.homeSeoTitle')
                                        ->label(self::FIELDS['homeSeoTitle'].' (Türkçe)')
                                        ->maxLength(60),
                                    Textarea::make('value.tr.homeSeoDescription')
                                        ->label(self::FIELDS['homeSeoDescription'].' (Türkçe)')
                                        ->rows(3)
                                        ->maxLength(160),
                                    TextInput::make('value.tr.homeSeoKeywords')
                                        ->label(self::FIELDS['homeSeoKeywords'].' (Türkçe)')
                                        ->helperText('Virgülle ayırın.'),
                                    StorageUpload::image('value.general.homeSeoShareImage', 'site', 'seo')
                                        ->label('Ana sayfa paylaşım görseli')
                                        ->imageEditor()
                                        ->helperText('Önerilen ölçü: 1200 × 630 px. Boşsa sitenin varsayılan paylaşım görseli kullanılır.'),
                                ]),
                        ]),
                ]),
        ]);
    }
}
