<?php

namespace App\Filament\Resources\ContentPages;

use App\Filament\Resources\ContentPages\Pages\CreateContentPage;
use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Filament\Resources\ManagedResource;
use App\Filament\Support\Multilingual;
use App\Filament\Support\Reorderable;
use App\Models\ContentPage;
use App\Support\Storefront;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ContentPageResource extends ManagedResource
{
    protected static ?string $model = ContentPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Bilgilendirme Sayfaları';

    protected static ?int $navigationSort = 120;

    protected static ?string $modelLabel = 'bilgilendirme sayfası';

    protected static ?string $pluralModelLabel = 'bilgilendirme sayfaları';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sayfa')
                ->description('Metinleri Türkçe girin; diğer 9 dil kaydederken otomatik hazırlanır.')
                // Tam sayfa Create/Edit'te Filament form kök gridini 2 kolon yapar
                // (EditRecord::configureForm). Section varsayılan olarak tek kolon
                // kaplayıp kart yarım genişlikte kalıyordu; kartı tam genişlik yapıyoruz.
                ->columnSpanFull()
                // Kısa alanlar kart içinde yan yana dursun.
                ->columns(2)
                ->schema([
                    Multilingual::turkish('title', 'Başlık')
                        ->columnSpan(1)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                    TextInput::make('slug')
                        ->label('URL kısa adı')
                        ->helperText('Türkçe sayfa /{kısa-ad}, diğer diller /en/{kısa-ad} benzeri adreslerde yayınlanır.')
                        ->columnSpan(1)
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->rule(fn () => function (string $attribute, $value, callable $fail) {
                            if (Storefront::isReservedSlug($value)) {
                                $fail('Bu kısa ad sitenin sabit sayfalarına ait, başka bir ad seçin.');
                            }
                        }),
                    Multilingual::turkish('content', 'İçerik', rich: true),
                    Multilingual::turkish('seo_title', 'SEO başlığı', required: false),
                    Multilingual::turkish('seo_description', 'SEO açıklaması', long: true, required: false),
                    // Sıra otomatik: yeni sayfa sona eklenir, tabloda "Manuel
                    // sırayı düzenle" ile sürükle-bırakla değiştirilir.
                    Toggle::make('active')->label('Yayında')->default(true)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(Reorderable::triggerAction())
            ->columns([
                TextColumn::make('title')->label('Başlık')->getStateUsing(fn ($record) => Multilingual::tr($record->title))->searchable(),
                TextColumn::make('slug')->label('URL')->color('gray'),
                ToggleColumn::make('active')->label('Yayında'),
                TextColumn::make('updated_at')->label('Güncellendi')->dateTime('d.m.Y H:i'),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn ($record): string => self::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentPages::route('/'),
            'create' => CreateContentPage::route('/create'),
            'edit' => EditContentPage::route('/{record}/edit'),
        ];
    }
}
