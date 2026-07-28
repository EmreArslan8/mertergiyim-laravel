<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Services\WhatsAppNotifier;
use App\Support\OrderStatus;
use App\Support\Storefront;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')->label('Sipariş no')->weight('bold')->searchable(),
                TextColumn::make('customer_name')
                    ->label('Müşteri')
                    ->description(fn ($record) => $record->phone)
                    ->searchable(),
                TextColumn::make('tracking_code')->label('Takip kodu')->placeholder('-')->searchable(),
                TextColumn::make('total')
                    ->label('Toplam')
                    ->formatStateUsing(fn ($state, $record) => Storefront::formatPrice($state, match ($record->currency ?? 'TRY') {
                        'USD' => ['symbol' => '$', 'position' => 'prefix'],
                        'EUR' => ['symbol' => '€', 'position' => 'suffix'],
                        default => ['symbol' => 'TL', 'position' => 'suffix'],
                    })),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => OrderStatus::label($state))
                    ->color(fn ($state) => OrderStatus::color($state)),
                TextColumn::make('cargo_company')->label('Kargo')->placeholder('-')->toggleable(),
                TextColumn::make('whatsapp_notified_at')
                    ->label('WhatsApp')
                    ->badge()
                    ->state(fn ($record) => $record->whatsapp_notified_at ? 'Gönderildi' : 'Gönderilmedi')
                    ->color(fn ($record) => $record->whatsapp_notified_at ? 'success' : 'danger')
                    ->tooltip(fn ($record) => $record->whatsapp_error),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Durum')->options(OrderStatus::labels()),
                TernaryFilter::make('whatsapp_notified_at')
                    ->label('WhatsApp bildirimi')
                    ->placeholder('Hepsi')
                    ->trueLabel('Gönderildi')
                    ->falseLabel('Gönderilmedi')
                    ->nullable(),
            ])
            ->recordActions([
                Action::make('resendWhatsapp')
                    ->label('WhatsApp bildirimini gönder')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->visible(fn ($record) => ! $record->whatsapp_notified_at)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        try {
                            app(WhatsAppNotifier::class)->sendOrderNotification($record->loadMissing('items'));
                            $record->forceFill(['whatsapp_notified_at' => now(), 'whatsapp_error' => null])->save();

                            Notification::make()->success()->title('Bildirim gönderildi.')->send();
                        } catch (\Throwable $exception) {
                            $record->forceFill(['whatsapp_error' => mb_substr($exception->getMessage(), 0, 500)])->save();

                            Notification::make()->danger()
                                ->title('Bildirim gönderilemedi.')
                                ->body($exception->getMessage())
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
