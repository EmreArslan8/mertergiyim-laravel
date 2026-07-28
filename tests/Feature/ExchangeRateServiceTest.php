<?php

namespace Tests\Feature;

use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    public function test_it_fetches_and_caches_try_exchange_rates(): void
    {
        Cache::forget('exchange:try:usd-eur:fresh');
        Cache::forget('exchange:try:usd-eur:last-good');
        Http::fake([
            'www.tcmb.gov.tr/*' => Http::response(
                '<?xml version="1.0"?><Tarih_Date Tarih="28.07.2026">'.
                '<Currency CurrencyCode="USD"><ForexSelling>32.0000</ForexSelling></Currency>'.
                '<Currency CurrencyCode="EUR"><ForexSelling>40.0000</ForexSelling></Currency>'.
                '</Tarih_Date>',
                200,
                ['Content-Type' => 'application/xml'],
            ),
        ]);

        $service = app(ExchangeRateService::class);

        $expected = ['USD' => 0.03125, 'EUR' => 0.025, 'source' => 'TCMB', 'date' => '28.07.2026'];
        $this->assertSame($expected, $service->ratesFromTry());
        $this->assertSame($expected, $service->ratesFromTry());

        Http::assertSentCount(1);
    }
}
