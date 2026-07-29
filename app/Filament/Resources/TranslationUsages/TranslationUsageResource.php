<?php

namespace App\Filament\Resources\TranslationUsages;

use App\Filament\Resources\ManagedResource;
use App\Filament\Resources\TranslationUsages\Pages\ListTranslationUsages;
use App\Models\TranslationUsage;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Gemini çeviri isteklerinin token kullanımı (salt okunur liste).
 */
class TranslationUsageResource extends ManagedResource
{
    protected static ?string $model = TranslationUsage::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Çeviri Kullanımı';

    protected static ?string $modelLabel = 'çeviri kaydı';

    protected static ?string $pluralModelLabel = 'çeviri kayıtları';

    protected static ?int $navigationSort = 14;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('model')->label('Model')->badge(),
                TextColumn::make('prompt_tokens')->label('Girdi token')->numeric()->sortable()->summarize(
                    Sum::make()->label('Toplam')
                ),
                TextColumn::make('output_tokens')->label('Çıktı token')->numeric()->sortable()->summarize(
                    Sum::make()->label('Toplam')
                ),
                TextColumn::make('total_tokens')->label('Toplam token')->numeric()->sortable()->summarize(
                    Sum::make()->label('Toplam')
                ),
                IconColumn::make('ok')->label('Başarılı')->boolean(),
                TextColumn::make('error')->label('Hata')->placeholder('-')->limit(60)->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('ok')->label('Durum'),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranslationUsages::route('/'),
        ];
    }
}
