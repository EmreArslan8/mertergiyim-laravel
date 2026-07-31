<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderDetail;
use App\Models\Order;
use App\Support\OrderStatus;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EditOrder extends EditRecord
{
    use HasBackToListAction;

    protected static string $resource = OrderResource::class;

    /**
     * Detay sayfası kaynağın oluşturma formunu kullanmaz: sipariş vitrinden
     * geldiği için burada alanların çoğu salt okunur gösterilir, yalnızca
     * kargo bilgisi ve dahili not düzenlenebilir. Yeni sipariş oluşturma
     * formu ayrı kaldı (OrderForm).
     */
    public function form(Schema $schema): Schema
    {
        return OrderDetail::configure($schema);
    }

    public function getTitle(): string
    {
        return 'Sipariş '.$this->getRecord()->order_number;
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return collect([
            $record->created_at?->translatedFormat('d M Y, H:i'),
            OrderStatus::label($record->status),
            $record->items()->count().' kalem',
        ])->filter()->join(' · ');
    }

    /**
     * Aynı eylemler iki biçimde tanımlanır: masaüstünde yan yana butonlar,
     * mobilde başlığın sağındaki üç nokta menüsü. Filament aksiyonları sunucu
     * tarafında üretildiği için ekran genişliğine göre biçim değiştiremiyor;
     * iki takım da render edilip görünürlük CSS ile ayrılır (butonlar
     * `merter-desktop-actions`, menü `merter-mobile-actions`).
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->advanceStatusAction()->extraAttributes(['class' => 'merter-desktop-actions']),
            $this->cancelOrderAction()->extraAttributes(['class' => 'merter-desktop-actions']),
            DeleteAction::make()->outlined()->extraAttributes(['class' => 'merter-desktop-actions']),

            ActionGroup::make([
                $this->advanceStatusAction('advanceStatusCompact'),
                $this->cancelOrderAction('cancelOrderCompact'),
                DeleteAction::make('deleteCompact'),
            ])
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->extraAttributes(['class' => 'merter-mobile-actions']),
        ];
    }

    /**
     * Durum yedi seçenekli bir listeden değil, tek butonla ilerletilir:
     * akıştaki bir sonraki adım zaten belli.
     */
    private function advanceStatusAction(string $name = 'advanceStatus'): Action
    {
        return Action::make($name)
            ->label(fn (): string => self::advanceLabel(OrderStatus::next($this->getRecord()->status)))
            ->icon(Heroicon::OutlinedArrowRight)
            ->visible(fn (): bool => OrderStatus::next($this->getRecord()->status) !== null)
            ->requiresConfirmation()
            ->modalHeading(fn (): string => self::advanceLabel(OrderStatus::next($this->getRecord()->status)))
            ->modalDescription(function (): string {
                $record = $this->getRecord();
                $next = OrderStatus::next($record->status);

                $description = sprintf(
                    'Sipariş "%s" durumundan "%s" durumuna geçecek.',
                    OrderStatus::label($record->status),
                    OrderStatus::label($next),
                );

                // "Kargoya verildi" deyip kargo firması girmemek, müşterinin
                // takip edememesi demek: geçiş engellenmez ama uyarılır.
                if ($next === 'shipped' && blank($record->cargo_company)) {
                    $description .= ' Kargo firması henüz girilmedi.';
                }

                return $description;
            })
            ->action(function (): void {
                $record = $this->getRecord();
                $next = OrderStatus::next($record->status);

                if ($next === null) {
                    return;
                }

                $record->forceFill(['status' => $next])->save();
                // Form state'i kayıttan geri okunmalı: aksi hâlde "Kaydet"
                // butonu ekrandaki eski durumu geri yazardı.
                $this->refreshFormData(['status']);

                Notification::make()
                    ->success()
                    ->title('Durum güncellendi: '.OrderStatus::label($next))
                    ->send();
            });
    }

    private function cancelOrderAction(string $name = 'cancelOrder'): Action
    {
        return Action::make($name)
            ->label('Siparişi iptal et')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            // Sayfanın birincil eylemi sıradaki adıma geçirmek; yıkıcı
            // eylemler dolu değil çerçeveli, görsel ağırlıkları düşük.
            ->outlined()
            ->visible(fn (): bool => ! in_array($this->getRecord()->status, ['cancelled', 'completed'], strict: true))
            ->requiresConfirmation()
            ->modalDescription('Sipariş normal akışın dışına çıkarılır.')
            ->action(function (): void {
                $this->getRecord()->forceFill(['status' => 'cancelled'])->save();
                $this->refreshFormData(['status']);

                Notification::make()->warning()->title('Sipariş iptal edildi.')->send();
            });
    }

    private static function advanceLabel(?string $next): string
    {
        return match ($next) {
            'confirmed' => 'Siparişi onayla',
            'paid' => 'Ödendi olarak işaretle',
            'preparing' => 'Hazırlanıyor olarak işaretle',
            'shipped' => 'Kargoya verildi olarak işaretle',
            'completed' => 'Tamamlandı olarak işaretle',
            default => 'Sonraki adıma geçir',
        };
    }

    /**
     * Kaydetme anında yönlendirme yapılmaz.
     *
     * Sunucu tarafında hemen yönlendirilince "kaydedildi" bildirimi sayfa
     * değişirken kayboluyor, kullanıcı kaydın gerçekleştiğini göremeden liste
     * ekranına düşüyordu. Bunun yerine bildirim mevcut sayfada gösterilir,
     * kısa bir gecikmeden sonra listeye SPA geçişiyle (tam sayfa yenilemesi
     * olmadan) dönülür.
     */
    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    protected function afterSave(): void
    {
        $this->js(sprintf(
            'setTimeout(() => Livewire.navigate(%s), 1000)',
            json_encode(static::getResource()::getUrl('index'), JSON_THROW_ON_ERROR),
        ));
    }

    protected function backToListLabel(): string
    {
        return 'Siparişlere dön';
    }

    public function getRecord(): Order
    {
        /** @var Order $record */
        $record = parent::getRecord();

        return $record;
    }
}
