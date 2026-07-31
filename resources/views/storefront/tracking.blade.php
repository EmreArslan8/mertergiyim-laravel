@php
    $t = $messages['tracking'] ?? [];
    $metaTitle = data_get($messages, 'tracking.heading').' | '.$siteName;
    $metaDescription = $messages['meta']['description'] ?? '';
    $metaKeywords = '';

    $stepKeys = ['new', 'confirmed', 'paid', 'shipped', 'completed'];

    // OrderTrackingForm.tsx: activeStepFor / progressFor
    $activeStepFor = fn (string $status) => match ($status) {
        'completed' => 4,
        'shipped' => 3,
        'paid', 'preparing' => 2,
        'confirmed' => 1,
        'cancelled' => -1,
        default => 0,
    };
    $progressFor = fn (string $status) => match ($status) {
        'completed' => 100,
        'shipped' => 75,
        'preparing' => 62.5,
        'paid' => 50,
        'confirmed' => 25,
        default => 0,
    };

    $cargoUrl = null;
    if ($order && $order['cargo_tracking_url']) {
        $scheme = parse_url($order['cargo_tracking_url'], PHP_URL_SCHEME);
        $cargoUrl = in_array($scheme, ['http', 'https'], true) ? $order['cargo_tracking_url'] : null;
    }

    $activeStep = $order ? $activeStepFor($order['status']) : -1;
    $orderItems = $order ? $order['items'] : [];
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/order-tracking.css?v=20260731-6">
@endpush

@section('content')
    <main class="tracking-page" dir="{{ $dir }}">
        <section class="tracking-content">
            @include('storefront.partials.page-heading', [
                'class' => 'page-heading--embedded',
                'title' => $t['heading'] ?? '',
                'description' => $t['subheading'] ?? '',
            ])

            <div class="tracking-panel">
                <form class="tracking-form" method="get" action="/{{ $locale }}/siparis-takibi" data-tracking-form>
                    <label for="tracking-query">{{ $t['inputLabel'] ?? '' }}</label>
                    <div class="tracking-query-row">
                        <div class="tracking-query-field">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"/>
                                <path d="m20 20-4-4"/>
                            </svg>
                            <input id="tracking-query" name="q" value="{{ $query }}"
                                   placeholder="{{ $t['placeholder'] ?? '' }}" autocomplete="off" spellcheck="false" required
                                   @if ($error) aria-describedby="tracking-error" aria-invalid="true" @endif>
                        </div>
                        <button type="submit" data-tracking-submit>
                            <span data-tracking-submit-label data-loading-text="{{ $t['loading'] ?? '' }}">{{ $t['submit'] ?? '' }}</span>
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6"/>
                            </svg>
                        </button>
                    </div>
                </form>

                @if ($error)
                    <p id="tracking-error" class="tracking-error" role="alert">{{ $error }}</p>
                @endif
            </div>

            @if ($order)
                <section class="tracking-result-grid" aria-live="polite">
                    <article class="tracking-summary-card">
                        <div class="tracking-summary-head">
                            <div>
                                <span class="tracking-kicker">{{ $t['orderNo'] ?? '' }}</span>
                                <strong class="tracking-order-number">{{ $order['order_number'] }}</strong>
                                <span class="tracking-kicker">{{ $t['trackingCode'] ?? '' }}</span>
                                <code class="tracking-code">{{ $order['tracking_code'] ?: ($t['notGenerated'] ?? '') }}</code>
                                <strong class="tracking-customer">{{ $t['customer'] ?? '' }}: {{ $order['customer_masked'] }}</strong>
                            </div>
                            <span class="tracking-status-badge status-{{ $order['status'] }}">{{ $t['statuses'][$order['status']] ?? $order['status'] }}</span>
                        </div>

                        <div class="tracking-cargo-block">
                            <h2>{{ $t['cargoInfo'] ?? '' }}</h2>
                            @if ($order['cargo_company'] || $cargoUrl)
                                <div class="tracking-cargo-info">
                                    <div><span>{{ $t['cargoCompany'] ?? '' }}</span><strong>{{ $order['cargo_company'] ?: ($t['notSpecified'] ?? '') }}</strong></div>
                                    @if ($order['tracking_code'])
                                        <div><span>{{ $t['trackingCode'] ?? '' }}</span><strong>{{ $order['tracking_code'] }}</strong></div>
                                    @endif
                                    @if ($cargoUrl)
                                        <a href="{{ $cargoUrl }}" target="_blank" rel="noreferrer">
                                            {{ $t['trackAtCargo'] ?? '' }}
                                            @include('storefront.partials.icon', ['name' => 'external-link', 'size' => 14])
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="tracking-cargo-empty">{{ $t['cargoEmpty'] ?? '' }}</div>
                            @endif
                        </div>
                    </article>

                    <div class="tracking-result-main">
                        <article class="tracking-status-card">
                            <h2>{{ $t['orderStatus'] ?? '' }}</h2>
                            @if ($order['status'] === 'cancelled')
                                <div class="order-stepper cancelled">
                                    <div class="os-head">
                                        <strong class="os-title">{{ $t['cancelledTitle'] ?? '' }}</strong>
                                        <span class="os-badge">{{ $t['cancelledBadge'] ?? '' }}</span>
                                    </div>
                                    <div class="os-cancel-note">{{ $t['cancelledNote'] ?? '' }}</div>
                                </div>
                            @else
                                <div class="order-stepper" role="list">
                                    <div class="os-row">
                                        @foreach ($stepKeys as $index => $stepKey)
                                            @php
                                                $state = $index < $activeStep ? 'is-done' : ($index === $activeStep ? 'is-active' : 'is-todo');
                                                $activeText = $order['status'] === 'preparing' && $index === $activeStep
                                                    ? ($t['preparingText'] ?? '')
                                                    : ($t['currentStage'] ?? '');
                                            @endphp
                                            <div class="os-step {{ $state }}" role="listitem" @if ($index === $activeStep) aria-current="step" @endif>
                                                <div class="os-cap">
                                                    <span class="os-num">{{ $index + 1 }}</span>
                                                </div>
                                                <div class="os-body">
                                                    <strong class="os-label">{{ $t['statuses'][$stepKey] ?? '' }}</strong>
                                                    <span class="os-sub">{{ $index < $activeStep ? ($t['done'] ?? '') : ($index === $activeStep ? $activeText : ($t['waiting'] ?? '')) }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="os-foot">
                                        <div class="os-progress" aria-hidden="true">
                                            <div class="os-bar" style="width: {{ $progressFor($order['status']) }}%"></div>
                                        </div>
                                        <p class="os-meta">{{ $t['statusNote'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endif
                        </article>

                        <article class="tracking-items-card">
                            <div class="tracking-items-head">
                                <h2>{{ $t['items'] ?? '' }}</h2>
                                <span>{{ count($orderItems) }} {{ $t['itemUnit'] ?? '' }}</span>
                            </div>
                            @if (count($orderItems))
                                <div class="tracking-table-wrap">
                                    <table class="tracking-items-table">
                                        <thead><tr><th>{{ $t['colProduct'] ?? '' }}</th><th>{{ $t['colQty'] ?? '' }}</th></tr></thead>
                                        <tbody>
                                            @foreach ($orderItems as $item)
                                                @php
                                                    $meta = array_filter([
                                                        $item->product_code ? ($t['code'] ?? '').': '.$item->product_code : null,
                                                        $item->size ? ($t['size'] ?? '').': '.$item->size : null,
                                                        $item->color ? ($t['color'] ?? '').': '.$item->color : null,
                                                    ]);
                                                @endphp
                                                <tr>
                                                    <td><strong>{{ $item->product_name }}</strong><span>{{ implode(' · ', $meta) }}</span></td>
                                                    <td>{{ $item->quantity }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="tracking-items-empty">{{ $t['itemsEmpty'] ?? '' }}</p>
                            @endif
                        </article>
                    </div>
                </section>
            @endif

        </section>
    </main>
@endsection
