<?php

namespace App\Filament\Resources\ContentPages;

use App\Filament\Resources\ContentPages\Pages\CreateContentPage;
use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Filament\Resources\ManagedResource;
use App\Filament\Support\Multilingual;
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

    protected static string|\UnitEnum|null $navigationGroup = 'Vitrin';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'bilgilendirme sayfası';

    protected static ?string $pluralModelLabel = 'bilgilendirme sayfaları';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sayfa')
                ->description('Metinleri Türkçe girin; diğer 9 dil kaydederken otomatik hazırlanır.')
                ->schema([
                    Multilingual::turkish('title', 'Başlık')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                    TextInput::make('slug')
                        ->label('URL kısa adı')
                        ->helperText('Sayfa /tr/{kısa-ad} adresinde yayınlanır.')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->rule(fn () => function (string $attribute, $value, callable $fail) {
                            if (Storefront::isReservedSlug($value)) {
                                $fail('Bu kısa ad sitenin sabit sayfalarına ait, başka bir ad seçin.');
                            }
                        }),
                    Multilingual::turkish('content', 'İçerik', long: true),
                    Multilingual::turkish('seo_title', 'SEO başlığı', required: false),
                    Multilingual::turkish('seo_description', 'SEO açıklaması', long: true, required: false),
                    TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                    Toggle::make('active')->label('Yayında')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
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
