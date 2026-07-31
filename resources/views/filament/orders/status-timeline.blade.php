@php
    use App\Support\OrderStatus;

    $record = $getRecord();
    // Kart, seçim yapılan yer: kaydedilmemiş seçimi de yansıtsın diye durum
    // formun canlı state'inden okunur, kayıttan değil.
    $status = $getLivewire()->data['status'] ?? $record?->status;

    $flow = OrderStatus::flow();
    $currentStep = OrderStatus::step($status);
    $cancelled = OrderStatus::isCancelled($status);
@endphp

{{-- Etiket, yanındaki "Müşteri takip kodu" alanıyla aynı hizadan başlaması
     için var: iki sütun da etiket + içerik ritmiyle diziliyor. --}}
<div @class(['merter-order-progress', 'merter-order-progress-cancelled' => $cancelled])>
    <span class="merter-order-progress-label">İlerleme</span>

    <div class="merter-order-progress-track">
        @foreach ($flow as $index => $step)
            <span
                @class([
                    'merter-order-progress-seg',
                    'is-done' => ! $cancelled && $index < $currentStep,
                    'is-current' => ! $cancelled && $index === $currentStep,
                ])
                title="{{ OrderStatus::label($step) }}"
            ></span>
        @endforeach
    </div>

    <p class="merter-order-progress-meta">
        @if ($cancelled)
            Sipariş iptal edildi.
        @else
            {{ $currentStep + 1 }}/{{ count($flow) }} · {{ OrderStatus::label($status) }}
            @if ($next = OrderStatus::next($status))
                <span class="merter-order-progress-next">→ sıradaki: {{ OrderStatus::label($next) }}</span>
            @else
                <span class="merter-order-progress-next">→ akış tamamlandı</span>
            @endif
        @endif
    </p>
</div>
