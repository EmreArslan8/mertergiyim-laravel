<?php

namespace App\Filament\Resources\BlogPosts;

use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Filament\Resources\ManagedResource;
use App\Filament\Support\Multilingual;
use App\Filament\Support\StorageUpload;
use App\Models\BlogPost;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogPostResource extends ManagedResource
{
    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'Blog Sayfaları';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'blog yazısı';

    protected static ?string $pluralModelLabel = 'blog yazıları';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Blog yazısı')->schema([
                Multilingual::turkish('title', 'Başlık')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->label('URL kısa adı')->required()->unique(ignoreRecord: true),
                Multilingual::turkish('excerpt', 'Özet', long: true, required: false),
                Multilingual::turkish('content', 'İçerik', long: true),
                StorageUpload::image('cover_image', 'site', 'blog')->label('Kapak görseli'),
                DateTimePicker::make('published_at')->label('Yayın tarihi')->default(now()),
                Toggle::make('active')->label('Yayında')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->stackedOnMobile()->defaultSort('published_at', 'desc')->columns([
            TextColumn::make('title')->label('Başlık')->getStateUsing(fn ($record) => Multilingual::tr($record->title))->searchable(),
            TextColumn::make('published_at')->label('Yayın tarihi')->dateTime('d.m.Y H:i'),
            ToggleColumn::make('active')->label('Yayında'),
        ])->recordActions([
            EditAction::make()
                ->url(fn ($record): string => self::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogPosts::route('/'),
            'create' => CreateBlogPost::route('/create'),
            'edit' => EditBlogPost::route('/{record}/edit'),
        ];
    }
}
