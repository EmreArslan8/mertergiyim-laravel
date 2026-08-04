@php
    use App\Support\Storefront;

    $pageTitle = data_get($messages, 'cart.bankAccounts') ?: 'Banka Hesaplarımız';
    $eyebrow = data_get($messages, 'bankAccounts.eyebrow') ?: 'Bilgilendirme';
    $subtitle = data_get($messages, 'bankAccounts.subtitle')
        ?: 'Sipariş sonrası ödeme teyidi için kullanabileceğiniz güncel banka hesap bilgilerimiz.';

    $ownerLabel = data_get($messages, 'bankAccounts.owner') ?: 'Hesap Sahibi';
    $ibanLabel = data_get($messages, 'bankAccounts.iban') ?: 'IBAN';
    $branchLabel = data_get($messages, 'bankAccounts.branch') ?: 'Şube';
    $copyLabel = data_get($messages, 'bankAccounts.copy') ?: 'IBAN kopyala';
    $copiedLabel = data_get($messages, 'bankAccounts.copied') ?: 'Kopyalandı';
    $emptyLabel = data_get($messages, 'bankAccounts.empty') ?: 'Şu anda görüntülenecek banka hesabı bulunmuyor.';

    $metaTitle = $pageTitle.' | '.$siteName;
    $metaDescription = $subtitle;
@endphp

@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/css/bank-accounts.css?v=20260804-1">
@endpush

@section('content')
    <main class="bank-page" dir="{{ $dir }}">
        @include('storefront.partials.page-heading', [
            'eyebrow' => $eyebrow,
            'title' => $pageTitle,
            'description' => $subtitle,
        ])

        <section class="bank-wrap" aria-label="{{ $pageTitle }}">
            @if ($accounts->isEmpty())
                <p class="bank-empty">{{ $emptyLabel }}</p>
            @else
                <div class="bank-grid">
                    @foreach ($accounts as $account)
                        <article class="bank-card">
                            <header class="bank-card-head">
                                @if ($account['logo'])
                                    <span class="bank-card-logo">
                                        <img src="{{ $account['logo'] }}" alt="{{ $account['bank_name'] }}" loading="lazy" width="120" height="36">
                                    </span>
                                @endif
                                <div class="bank-card-title">
                                    <h2>{{ $account['bank_name'] }}</h2>
                                    @if ($account['account_type'] !== '')
                                        <span class="bank-card-badge">{{ $account['account_type'] }}</span>
                                    @endif
                                </div>
                            </header>

                            <dl class="bank-card-body">
                                <div class="bank-field">
                                    <dt>{{ $ownerLabel }}</dt>
                                    <dd>{{ $account['account_holder'] }}</dd>
                                </div>

                                <div class="bank-field bank-field--iban">
                                    <dt>{{ $ibanLabel }}</dt>
                                    <dd>
                                        <span class="bank-iban">{{ $account['iban'] }}</span>
                                        <button type="button" class="bank-copy"
                                                data-copy="{{ str_replace(' ', '', $account['iban']) }}"
                                                data-copied="{{ $copiedLabel }}"
                                                aria-label="{{ $copyLabel }}" title="{{ $copyLabel }}">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <rect x="9" y="9" width="12" height="12" rx="2"></rect>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                            </svg>
                                        </button>
                                    </dd>
                                </div>

                                @if ($account['branch'] !== '')
                                    <div class="bank-field">
                                        <dt>{{ $branchLabel }}</dt>
                                        <dd>{{ $account['branch'] }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.bank-copy').forEach(function (button) {
            button.addEventListener('click', function () {
                var iban = button.getAttribute('data-copy') || '';
                var done = function () {
                    button.classList.add('is-copied');
                    button.setAttribute('data-tooltip', button.getAttribute('data-copied'));
                    setTimeout(function () {
                        button.classList.remove('is-copied');
                        button.removeAttribute('data-tooltip');
                    }, 1600);
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(iban).then(done).catch(done);
                } else {
                    var input = document.createElement('textarea');
                    input.value = iban;
                    document.body.appendChild(input);
                    input.select();
                    try { document.execCommand('copy'); } catch (e) {}
                    document.body.removeChild(input);
                    done();
                }
            });
        });
    </script>
@endpush
