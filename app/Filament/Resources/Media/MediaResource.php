<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\ManagedResource;
use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Support\Multilingual;
use App\Filament\Support\StorageUpload;
use App\Models\MediaPost;
use App\Support\Storefront;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class MediaResource extends ManagedResource
{
    protected static ?string $model = MediaPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static ?string $navigationLabel = 'Multimedya';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'medya albümü';

    protected static ?string $pluralModelLabel = 'medya albümleri';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Albüm bilgileri')->schema([
                Multilingual::turkish('title', 'Başlık', required: false),
                Multilingual::turkish('description', 'Açıklama', long: true, required: false),
                TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Toggle::make('active')->label('Aktif')->default(true),
            ]),
            Section::make('Dosyalar')
                ->description('Bir albüme birden fazla görsel veya video ekleyebilirsiniz.')
                ->schema([
                    Repeater::make('files')
                        ->hiddenLabel()
                        ->relationship('files')
                        ->orderColumn('sort_order')
                        ->schema([
                            Select::make('type')
                                ->label('Tür')
                                ->options([
                                    'image' => 'Görsel',
                                    'video' => 'Video',
                                    'document' => 'Belge',
                                ])
                                ->default('image')
                                ->required(),
                            StorageUpload::file('file_path', 'site')
                                ->label('Dosya')
                                ->required()
                                ->columnSpanFull(),
                            Multilingual::turkish('alt', 'Alternatif metin', required: false)
                                ->columnSpanFull(),
                            TextInput::make('sort_order')
                                ->label('Sıra')
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('Dosya ekle')
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => isset($state['file_path'])
                            ? basename((string) $state['file_path'])
                            : 'Yeni dosya'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->stackedOnMobile()->defaultSort('sort_order')->columns([
            ImageColumn::make('cover')->label('Kapak')->getStateUsing(
                function ($record): ?string {
                    $file = $record->files->firstWhere('type', 'image');

                    return $file ? Storefront::storageUrl('site', $file->file_path) : null;
                }
            ),
            TextColumn::make('title')->label('Başlık')->getStateUsing(fn ($record) => Multilingual::tr($record->title))->placeholder('-'),
            TextColumn::make('files_count')->label('Dosya')->counts('files')->badge(),
            ToggleColumn::make('active')->label('Aktif / Pasif'),
        ])->recordActions([
            EditAction::make()
                ->url(fn ($record): string => self::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'create' => CreateMedia::route('/create'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }
}
