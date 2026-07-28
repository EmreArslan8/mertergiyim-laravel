<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\ManagedResource;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Support\Multilingual;
use App\Filament\Support\StorageUpload;
use App\Models\Media;
use App\Support\Storefront;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MediaResource extends ManagedResource
{
    protected static ?string $model = Media::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;
    protected static ?string $navigationLabel = 'Multimedya';
    protected static ?string $modelLabel = 'medya';
    protected static ?string $pluralModelLabel = 'multimedya';
    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dosya')->schema([
                Select::make('type')->label('Tür')->options([
                    'image' => 'Görsel', 'video' => 'Video', 'document' => 'Belge',
                ])->default('image')->required(),
                StorageUpload::file('file_path', 'site')->label('Dosya')->required(),
                Multilingual::turkish('title', 'Başlık', required: false),
                Multilingual::turkish('alt', 'Alternatif metin', required: false),
                Multilingual::turkish('caption', 'Açıklama', long: true, required: false),
                TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Toggle::make('active')->label('Aktif')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->stackedOnMobile()->defaultSort('sort_order')->columns([
            ImageColumn::make('file_path')->label('Önizleme')->getStateUsing(
                fn ($record) => $record->type === 'image' ? Storefront::storageUrl('site', $record->file_path) : null
            ),
            TextColumn::make('title')->label('Başlık')->getStateUsing(fn ($record) => Multilingual::tr($record->title))->placeholder('-'),
            TextColumn::make('type')->label('Tür')->badge(),
            ToggleColumn::make('active')->label('Aktif / Pasif'),
        ])->recordActions([
            EditAction::make()->mutateDataUsing(fn (array $data, $livewire, ?Model $record): array => $livewire->fillAutomaticTranslationsFor($data, $record)),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMedia::route('/')];
    }
}
