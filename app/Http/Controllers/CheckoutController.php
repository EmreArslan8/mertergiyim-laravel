<?php

namespace App\Http\Controllers;

use App\Jobs\NotifyStoreOfOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ExchangeRateService;
use App\Services\OrderCodeService;
use App\Support\PhoneNumber;
use App\Support\Storefront;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderCodeService $codes,
        private ExchangeRateService $exchangeRates,
    ) {}

    public function store(Request $request, string $locale): RedirectResponse
    {
        $items = json_decode((string) $request->input('cart'), true);
        $request->merge(['items' => is_array($items) ? $items : []]);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => [
                'required',
                'string',
                'max:30',
                PhoneNumber::rule($this->copy(
                    $locale,
                    'cart.errors.phone',
                    'Geçerli bir telefon numarası girin. Örnek: 0532 325 97 88',
                )),
            ],
            'address' => ['required', 'string', 'max:2000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'order_key' => ['nullable', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.size' => ['nullable', 'string', 'max:80'],
            'items.*.size_id' => ['nullable', 'uuid'],
            'items.*.color' => ['nullable', 'string', 'max:120'],
            'items.*.color_id' => ['nullable', 'uuid'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'items.required' => $this->copy($locale, 'cart.errors.empty', 'Sepetiniz boş.'),
            'items.min' => $this->copy($locale, 'cart.errors.empty', 'Sepetiniz boş.'),
        ]);

        $orderKey = $data['order_key'] ?? null;

        // Çift tıklama/yenileme: aynı anahtarla gelen ikinci istek yeni sipariş
        // açmaz, ilk siparişin takip sayfasına gider.
        if ($existing = $this->existingOrderFor($orderKey)) {
            return $this->toSuccess($locale, $existing);
        }

        $currency = match ($locale) {
            'tr' => 'TRY',
            'ar', 'fa' => 'USD',
            default => 'EUR',
        };
        $rates = $locale === 'tr' ? null : rescue(
            fn () => $this->exchangeRates->ratesFromTry(),
            report: true,
        );

        try {
            $order = $this->createOrder($data, $locale, $currency, $rates, $orderKey);
        } catch (UniqueConstraintViolationException $exception) {
            if ($existing = $this->existingOrderFor($orderKey)) {
                return $this->toSuccess($locale, $existing);
            }

            throw $exception;
        }

        // Yanıt gönderildikten sonra çalışır: bildirim yavaşlarsa ya da
        // başarısız olursa müşteri bekletilmez, sipariş etkilenmez.
        NotifyStoreOfOrder::dispatch($order)->afterResponse();

        return $this->toSuccess($locale, $order);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, float>|null  $rates
     */
    private function createOrder(array $data, string $locale, string $currency, ?array $rates, ?string $orderKey): Order
    {
        return DB::transaction(function () use ($data, $locale, $currency, $rates, $orderKey): Order {
            $productIds = collect($data['items'])->pluck('product_id')->unique()->values();
            $products = Product::query()
                ->active()
                ->with(['variants.size', 'variants.color'])
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'cart' => $this->copy($locale, 'cart.errors.unavailable', 'Sepetteki ürünlerden biri artık satışta değil.'),
                ]);
            }

            $lines = [];
            $total = 0.0;

            foreach ($data['items'] as $item) {
                /** @var Product $product */
                $product = $products->get($item['product_id']);

                if ($product->stock_status === 'out_of_stock') {
                    throw ValidationException::withMessages([
                        'cart' => $this->copy($locale, 'cart.errors.outOfStock', 'Sepetteki ürünlerden biri tükendi.'),
                    ]);
                }

                $variant = null;
                if ($product->variants->isNotEmpty()) {
                    $variant = $this->matchVariant($product, $item);

                    if (! $variant) {
                        throw ValidationException::withMessages([
                            'cart' => $this->copy($locale, 'cart.errors.variant', 'Seçilen beden ve renk kombinasyonu bulunamadı.'),
                        ]);
                    }

                    $lockedVariant = ProductVariant::query()->lockForUpdate()->find($variant->id);
                    if (! $lockedVariant || $lockedVariant->stock_quantity < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'cart' => $this->copy($locale, 'cart.errors.stock', 'Seçilen ürün için yeterli stok bulunmuyor.'),
                        ]);
                    }
                    $lockedVariant->decrement('stock_quantity', $item['quantity']);
                }

                $unitPrice = (float) $product->priceForLocale($locale, $rates);
                $lineTotal = round($unitPrice * $item['quantity'], 2);
                $total += $lineTotal;

                $lines[] = [
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'product_name' => Storefront::text($product->name, $locale),
                    'product_code' => $product->code,
                    // Panelde okunabilir olsun diye varyantın Türkçe adı yazılır;
                    // varyantsız üründe müşterinin gördüğü etiket kalır.
                    'size' => $variant?->size?->name ?? (($item['size'] ?? '') ?: null),
                    'color' => $variant?->color?->name ?? (($item['color'] ?? '') ?: null),
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $codes = $this->codes->generate();
            $order = Order::query()->create([
                ...$codes,
                'idempotency_key' => $orderKey,
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'note' => $data['note'] ?? null,
                'status' => 'new',
                'total' => round($total, 2),
                'currency' => $currency,
            ]);
            $order->items()->createMany($lines);

            return $order;
        }, 3);
    }

    /**
     * İki istek aynı anda ön kontrolü geçerse ikincisi unique kısıta takılır;
     * hata göstermek yerine ilk siparişin takip sayfasına gönderilir.
     */
    private function existingOrderFor(?string $orderKey): ?Order
    {
        return $orderKey
            ? Order::query()->where('idempotency_key', $orderKey)->first()
            : null;
    }

    private function toSuccess(string $locale, Order $order): RedirectResponse
    {
        return redirect()->route('storefront.order.success', [
            'locale' => $locale,
            'trackingCode' => $order->tracking_code,
        ]);
    }

    public function success(string $locale, string $trackingCode): View
    {
        $order = Order::query()
            ->with('items')
            ->where('tracking_code', $trackingCode)
            ->firstOrFail();

        return view('storefront.order-success', [
            'order' => $order,
            'canonicalPath' => '/'.$locale.'/siparis-basarili/'.$trackingCode,
            'alternatePath' => fn (string $code) => '/'.$code.'/siparis-basarili/'.$trackingCode,
        ]);
    }

    /**
     * Sepet satırını ürünün varyantlarından biriyle eşler.
     *
     * Ürün sayfası beden/renk adını aktif dile çevirerek gösterdiği için ada
     * göre eşleşme yabancı dillerde çalışmıyordu; öncelik size_id/color_id'de.
     * Ada göre eşleşme yalnızca bu alanları taşımayan eski sepetler için yedek.
     *
     * @param  array<string, mixed>  $item
     */
    private function matchVariant(Product $product, array $item): ?ProductVariant
    {
        $sizeId = $item['size_id'] ?? null;
        $colorId = $item['color_id'] ?? null;

        if ($sizeId !== null || $colorId !== null) {
            return $product->variants->first(fn ($candidate) => $candidate->size_id === $sizeId
                && $candidate->color_id === $colorId);
        }

        return $product->variants->first(fn ($candidate) => ($candidate->size?->name ?? '') === ($item['size'] ?? '')
            && ($candidate->color?->name ?? '') === ($item['color'] ?? ''));
    }

    private function copy(string $locale, string $key, string $fallback): string
    {
        return (string) app(\App\Services\DictionaryService::class)->get($locale, $key, $fallback);
    }
}
