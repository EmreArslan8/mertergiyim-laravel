<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ExchangeRateService
{
    /**
     * TRY bazlı USD ve EUR oranları. Birincil kaynak resmi TCMB günlük XML,
     * ikincil kaynak ECB verisini sunan Frankfurter'dır.
     *
     * @return array{USD: float, EUR: float, source: string, date: string}
     */
    public function ratesFromTry(): array
    {
        if ($cached = Cache::get('exchange:try:usd-eur:fresh')) {
            return $cached;
        }

        try {
            $rates = $this->fromTcmb();
        } catch (Throwable $tcmbException) {
            report($tcmbException);

            try {
                $rates = $this->fromFrankfurter();
            } catch (Throwable $fallbackException) {
                report($fallbackException);

                $lastGood = Cache::get('exchange:try:usd-eur:last-good');
                if (is_array($lastGood)) {
                    return $lastGood;
                }

                throw new RuntimeException('TCMB ve yedek kur servisine ulaşılamadı.', previous: $fallbackException);
            }
        }

        Cache::put('exchange:try:usd-eur:fresh', $rates, now()->addHours(6));
        Cache::forever('exchange:try:usd-eur:last-good', $rates);

        return $rates;
    }

    private function fromTcmb(): array
    {
        $body = Http::timeout(6)->retry(2, 250)
            ->get((string) config('storefront.exchange.tcmb_endpoint'))
            ->throw()
            ->body();
        $xml = simplexml_load_string($body);

        if ($xml === false) {
            throw new RuntimeException('TCMB kur XML dosyası okunamadı.');
        }

        $selling = [];
        foreach ($xml->Currency as $currency) {
            $code = (string) $currency['CurrencyCode'];
            if (in_array($code, ['USD', 'EUR'], true)) {
                $selling[$code] = (float) str_replace(',', '.', (string) $currency->ForexSelling);
            }
        }

        if (($selling['USD'] ?? 0) <= 0 || ($selling['EUR'] ?? 0) <= 0) {
            throw new RuntimeException('TCMB USD/EUR satış kuru bulunamadı.');
        }

        return [
            'USD' => 1 / $selling['USD'],
            'EUR' => 1 / $selling['EUR'],
            'source' => 'TCMB',
            'date' => (string) ($xml['Tarih'] ?? now()->toDateString()),
        ];
    }

    private function fromFrankfurter(): array
    {
        $base = rtrim((string) config('storefront.exchange.fallback_endpoint'), '/');
        $usdResponse = Http::acceptJson()->timeout(6)->retry(2, 250)
            ->get($base.'/v2/rate/TRY/USD', ['providers' => 'ECB'])
            ->throw();
        $eurResponse = Http::acceptJson()->timeout(6)->retry(2, 250)
            ->get($base.'/v2/rate/TRY/EUR', ['providers' => 'ECB'])
            ->throw();
        $usd = $usdResponse->json('rate');
        $eur = $eurResponse->json('rate');

        if (! is_numeric($usd) || ! is_numeric($eur)) {
            throw new RuntimeException('Yedek kur servisi geçerli USD/EUR oranı döndürmedi.');
        }

        return [
            'USD' => (float) $usd,
            'EUR' => (float) $eur,
            'source' => 'Frankfurter / ECB',
            'date' => (string) ($usdResponse->json('date') ?? now()->toDateString()),
        ];
    }
}
