<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\OrderStatus;
use App\Support\PhoneNumber;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Sipariş detay sayfası.
 *
 * Sipariş vitrindeki sepetten oluşuyor; bu sayfa bir "veri girme formu" değil,
 * kaydı okuyup üstünde işlem yapma ekranı. Bu yüzden sipariş no, müşteri, adres
 * ve kalemler salt okunur gösterilir; yalnızca panelin gerçekten değiştirdiği
 * üç şey düzenlenebilir: kargo bilgisi, dahili not ve (sayfa aksiyonlarından)
 * durum. Yeni sipariş oluşturma akışı ayrı: bkz. OrderForm.
 */
class OrderDetail
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            // Sayfa şeması varsayılan olarak iki sütun; kartların satırlarını
            // kendi Grid'leri belirlesin diye tek sütuna alınır.
            // Polaris'in "primary / secondary" düzeni: solda ⅔ genişlikte
            // sayfanın omurgası (kalemler), sağda ⅓ genişlikte bağlam ve
            // eylem kartları. Sağ kolon sayfa kaydıkça sabit kalır, 20
            // kalemli siparişte durum ve kargo gözden kaybolmasın.
            // Polaris'in "primary / secondary" düzeni: solda ⅔ genişlikte
            // sayfanın iş yapılan kısmı, sağda ⅓ genişlikte müşteri bağlamı.
            // Sağ kolon sayfa kaydıkça sabit kalır.
            ->columns(['default' => 1, 'xl' => 3])
            ->components([
                Group::make()
                    ->columnSpan(['default' => 1, 'xl' => 2])
                    ->schema([
                        // Sayfanın ilk kartı, siparişte yapılacak iş: durum ve
                        // kargo. Yan yana duruyorlar çünkü tek bir karar
                        // zincirinin iki ucu ("kargoya verildi" demeden önce
                        // firma girilir). Ürünler bunun altında: okunacak
                        // bilgi, eylemden sonra gelir.
                        Section::make('Durum ve kargo')
                            ->columns(['default' => 1, 'md' => 2])
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Select::make('status')
                                            ->label('Durum')
                                            ->options(OrderStatus::labels())
                                            ->selectablePlaceholder(false)
                                            ->live(),
                                        View::make('filament.orders.status-timeline'),
                                    ]),

                                Group::make()
                                    ->schema([
                                        // Aramalı select durum select'inden farklı
                                        // yükseklikteydi; liste kısa, arama yok.
                                        OrderItems::cargoCompanySelect(),

                                        TextEntry::make('tracking_code')
                                            ->label('Müşteri takip kodu')
                                            ->placeholder('Takip kodu yok')
                                            ->copyable()
                                            ->copyMessage('Takip kodu kopyalandı'),
                                    ]),
                            ]),

                        Section::make('Ürünler')
                            ->key('orderItems')
                            ->headerActions([self::editItemsAction()])
                            ->schema([View::make('filament.orders.items')]),
                    ]),

                // Sağ kolon salt okunur bağlam: siparişin kime gittiği.
                Group::make()
                    ->columnSpan(1)
                    ->extraAttributes(['class' => 'merter-order-side'])
                    ->schema([
                        Section::make('Müşteri')
                            ->key('customer')
                            ->headerActions([self::editCustomerAction()])
                            ->schema([
                                // Ad ve telefon yan yana: ikisi de tek satırlık
                                // ve panelde en çok bakılan iki alan.
                                View::make('filament.orders.customer'),

                                Html::make('<hr class="merter-order-hr">'),

                                View::make('filament.orders.address'),

                                Html::make(fn (?Order $record): string => filled($record?->note)
                                    ? '<hr class="merter-order-hr">'
                                    : ''),

                                // Not yoksa hem ayraç hem başlık boşuna yer
                                // kaplıyordu; blok tamamen gizlenir.
                                TextEntry::make('note')
                                    ->label('Müşteri notu')
                                    ->visible(fn (?Order $record): bool => filled($record?->note))
                                    ->formatStateUsing(fn (?string $state): string => '“'.$state.'”')
                                    ->extraAttributes(['class' => 'merter-order-customer-note']),
                            ]),
                    ]),
            ]);
    }


    /**
     * Müşteri bilgileri sayfada salt okunur duruyor: sipariş vitrinden geliyor,
     * yanlışlıkla değiştirilmesi kargonun yanlış adrese gitmesi demek. Yine de
     * düzeltme gerekebiliyor (telefon yanlış girilmiş, adres güncellenmiş);
     * bu yüzden alanlar açık input değil, kartın başlığındaki düzenle
     * aksiyonunun açtığı modalda.
     */
    private static function editCustomerAction(): Action
    {
        return Action::make('editCustomer')
            ->label('Düzenle')
            ->icon(Heroicon::OutlinedPencilSquare)
            // Kart başlığındaki eylem sayfanın birincil eylemi değil: dolu
            // siyah buton olarak sipariş durumunu ilerleten butonla yarışıyordu.
            ->link()
            ->color('gray')
            ->modalHeading('Müşteri bilgileri')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(fn (Order $record): array => $record->only([
                'customer_name', 'phone', 'address',
            ]))
            // Asgari bilgi seti oluşturma formuyla aynı: mevcut bir sipariş de
            // adresi boşaltılarak ya da telefonu bozularak kaydedilemez.
            ->schema([
                TextInput::make('customer_name')
                    ->label('Ad soyad')
                    ->required()
                    ->maxLength(120),
                TextInput::make('phone')
                    ->label('Telefon')
                    ->tel()
                    ->required()
                    ->maxLength(30)
                    ->rule(fn () => PhoneNumber::rule('Geçerli bir telefon numarası girin.')),
                Textarea::make('address')
                    ->label('Teslimat adresi')
                    ->rows(4)
                    ->required()
                    ->maxLength(2000),
            ])
            ->action(function (array $data, Order $record): void {
                $record->update($data);

                Notification::make()->success()->title('Müşteri bilgileri güncellendi.')->send();
            });
    }

    /**
     * Kalemler sayfada salt okunur duruyor; düzenleme, kartın başlığındaki
     * aksiyonun açtığı modalda yapılır.
     *
     * Ürün serbest metin değil, katalogdan seçilir: seçimle birlikte ürün adı,
     * kodu ve fiyatı doldurulur, varyant seçimiyle beden/renk bağlanır. Böylece
     * kalem gerçek ürüne (product_id / variant_id) bağlı kalır; yazım hatası
     * yüzünden katalogla eşleşmeyen sipariş satırı oluşmaz.
     */
    private static function editItemsAction(): Action
    {
        return Action::make('editItems')
            ->label('Kalemleri düzenle')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->link()
            ->color('gray')
            ->modalHeading('Sipariş kalemleri')
            ->modalSubmitActionLabel('Kalemleri kaydet')
            ->modalWidth(Width::FiveExtraLarge)
            // Kapanmış siparişin kalemleri değiştirilmez.
            ->visible(fn (Order $record): bool => ! in_array(
                $record->status,
                ['completed', 'cancelled'],
                strict: true,
            ))
            ->fillForm(fn (Order $record): array => [
                'items' => $record->items()
                    ->get()
                    ->map(fn (OrderItem $item): array => [
                        'id' => $item->getKey(),
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'product_name' => $item->product_name,
                        'product_code' => $item->product_code,
                        'size' => $item->size,
                        'color' => $item->color,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                    ])
                    ->all(),
            ])
            ->schema([
                Repeater::make('items')
                    ->hiddenLabel()
                    ->addActionLabel('Kalem ekle')
                    ->defaultItems(0)
                    ->itemLabel(fn (array $state): ?string => $state['product_name'] ?? null)
                    ->columns(['default' => 1, 'md' => 12])
                    ->schema([
                        Hidden::make('id'),
                        ...OrderItems::fields(),
                    ]),
            ])
            ->action(function (array $data, Order $record): void {
                $items = $data['items'] ?? [];
                $keptIds = [];

                foreach ($items as $item) {
                    $attributes = OrderItems::attributes($item);

                    // Var olan satır güncellenir, silinip yeniden yaratılmaz:
                    // kalem kimliği ve oluşturulma zamanı korunur.
                    $existing = filled($item['id'] ?? null)
                        ? $record->items()->whereKey($item['id'])->first()
                        : null;

                    if ($existing) {
                        $existing->update($attributes);
                        $keptIds[] = $existing->getKey();

                        continue;
                    }

                    $keptIds[] = $record->items()->create($attributes)->getKey();
                }

                $record->items()->whereKeyNot($keptIds)->delete();

                // Sipariş toplamı kalemlerden türetilir; kalem değişince eski
                // toplamın kalması sessiz bir hata oluyordu.
                OrderItems::recalculateTotal($record);

                $record->refresh();

                Notification::make()->success()->title('Kalemler güncellendi.')->send();
            });
    }




}
