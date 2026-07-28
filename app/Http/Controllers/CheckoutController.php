<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ExchangeRateService;
use App\Services\OrderCodeService;
use App\Support\Storefront;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:2000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.size' => ['nullable', 'string', 'max:80'],
            'items.*.color' => ['nullable', 'string', 'max:120'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'items.required' => $this->copy($locale, 'cart.errors.empty', 'Sepetiniz boş.'),
            'items.min' => $this->copy($locale, 'cart.errors.empty', 'Sepetiniz boş.'),
        ]);

        $currency = match ($locale) {
            'tr' => 'TRY',
            'ar', 'fa' => 'USD',
            default => 'EUR',
        };
        $rates = $locale === 'tr' ? null : rescue(
            fn () => $this->exchangeRates->ratesFromTry(),
            report: true,
        );

        $order = DB::transaction(function () use ($data, $locale, $currency, $rates): Order {
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
                    $variant = $product->variants->first(fn ($candidate) =>
                        ($candidate->size?->name ?? '') === ($item['size'] ?? '')
                        && ($candidate->color?->name ?? '') === ($item['color'] ?? '')
                    );

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
                    'size' => ($item['size'] ?? '') ?: null,
                    'color' => ($item['color'] ?? '') ?: null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $codes = $this->codes->generate();
            $order = Order::query()->create([
                ...$codes,
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

    private function copy(string $locale, string $key, string $fallback): string
    {
        return (string) app(\App\Services\DictionaryService::class)->get($locale, $key, $fallback);
    }
}
