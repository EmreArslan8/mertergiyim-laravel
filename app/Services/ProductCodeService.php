<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Ürün kodunu sistem atar; panelde elle girilmez.
 *
 * Numaralandırma MG-1001'den başlar ve birer birer artar: MG-1001, MG-1002 …
 * Önek sabit, numara sayaçtan gelir. Kod müşteriye "MG-1001" olarak gösterilir.
 */
class ProductCodeService
{
    private const PREFIX = 'MG-';

    /**
     * Sıradaki kod. Sayaç ürünlerden ayrı tutulduğu için ürün silinse bile
     * daha önce verilmiş bir numara yeniden kullanılamaz.
     */
    public function next(): string
    {
        return DB::transaction(function (): string {
            $sequence = DB::table('product_code_sequences')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            $next = ((int) $sequence->last_number) + 1;

            DB::table('product_code_sequences')
                ->where('id', 1)
                ->update(['last_number' => $next]);

            return self::format($next);
        });
    }

    /**
     * Sıradaki kodun önizlemesi. Sayacı ilerletmez; formda salt bilgi olarak
     * göstermek içindir. Gerçek atama kayıt anında next() ile yapılır; iki kişi
     * aynı anda kaydederse önizleme birebir tutmayabilir.
     */
    public function peek(): string
    {
        $last = (int) DB::table('product_code_sequences')
            ->where('id', 1)
            ->value('last_number');

        return self::format($last + 1);
    }

    private static function format(int $number): string
    {
        return self::PREFIX.$number;
    }

    /**
     * İçe aktarma gibi akışlarda açıkça verilen yüksek bir kod da sayacı
     * ileri taşır; otomatik numaralandırma geriye dönmez.
     */
    public function reserve(string $code): void
    {
        $number = (int) preg_replace('/\D+/', '', $code);

        if ($number < 1) {
            return;
        }

        DB::transaction(function () use ($number): void {
            $sequence = DB::table('product_code_sequences')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            if ($number > (int) $sequence->last_number) {
                DB::table('product_code_sequences')
                    ->where('id', 1)
                    ->update(['last_number' => $number]);
            }
        });
    }
}
