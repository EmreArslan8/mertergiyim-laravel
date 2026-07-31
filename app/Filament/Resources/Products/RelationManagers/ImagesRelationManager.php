<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Concerns\TranslatesJsonFields;
use App\Filament\Support\Multilingual;
use App\Filament\Support\StorageUpload;
use App\Services\ImageUploader;
use App\Support\Storefront;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ImagesRelationManager extends RelationManager
{
    use TranslatesJsonFields;

    protected static string $relationship = 'images';

    protected static ?string $title = 'Görseller';

    protected function translatableJsonFields(): array
    {
        return ['alt' => 'Alternatif metin'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            StorageUpload::image('storage_path', 'products', fn ($livewire) => (string) $livewire->getOwnerRecord()->getKey())
                ->label('Görsel')
                ->required()
                ->columnSpanFull(),
            Multilingual::turkish('alt', 'Alternatif metin', required: false),
            TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            Toggle::make('is_primary')->label('Birincil görsel'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('storage_path')
                    ->label('Görsel')
                    ->getStateUsing(fn ($record) => Storefront::storageUrl('products', $record->storage_path)),
                TextColumn::make('storage_path')->label('Yol')->color('gray')->limit(48),
                TextColumn::make('sort_order')->label('Sıra'),
                IconColumn::make('is_primary')->label('Birincil')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Görsel ekle')
                    ->mutateDataUsing(fn (array $data): array => $this->fillAutomaticTranslationsFor($data, null)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data, ?Model $record): array => $this->fillAutomaticTranslationsFor($data, $record)),
                // Kayıt silinince dosyayı da bucket'tan kaldır.
                DeleteAction::make()->before(fn ($record) => app(ImageUploader::class)->delete('products', $record->storage_path)),
            ]);
    }
}
