<?php

namespace App\Support;

/**
 * Sipariş ve iletişim formlarındaki telefon numarasını doğrular ve WhatsApp
 * Cloud API'nin beklediği biçime (ülke kodu + numara, yalnızca rakam) çevirir.
 *
 * Vitrin 10 dilde yayınlandığı için yalnızca Türkiye biçimi kabul edilemez;
 * kural uluslararası numaraları da geçirir, ülke kodsuz 10 haneli girişleri
 * varsayılan ülke koduna (90) tamamlar.
 */
class PhoneNumber
{
    private const DEFAULT_COUNTRY_CODE = '90';

    /**
     * Yalnızca rakamları bırakır, baştaki 00'ı ve Türkiye için baştaki 0'ı atar.
     */
    public static function normalize(?string $value): string
    {
        $value = trim((string) $value);
        $hadPlus = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }

        if ($hadPlus) {
            return $digits;
        }

        // "0532...", "532..." → ülke kodu eklenir.
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return self::DEFAULT_COUNTRY_CODE.substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            return self::DEFAULT_COUNTRY_CODE.$digits;
        }

        return $digits;
    }

    /**
     * E.164 uzunluk aralığı: ülke kodu dahil 8-15 hane.
     */
    public static function isValid(?string $value): bool
    {
        $raw = trim((string) $value);

        // Harf içeren ya da izinli ayraçlar dışında karakter taşıyan girişler
        // (ör. "telefon yok") elenir.
        if ($raw === '' || preg_match('/[^0-9+()\/.\-\s]/u', $raw)) {
            return false;
        }

        $normalized = self::normalize($raw);

        return strlen($normalized) >= 8 && strlen($normalized) <= 15;
    }

    /**
     * Laravel doğrulama kuralı olarak kullanılacak closure.
     */
    public static function rule(string $message): \Closure
    {
        return function (string $attribute, $value, callable $fail) use ($message) {
            if (! self::isValid($value)) {
                $fail($message);
            }
        };
    }
}
