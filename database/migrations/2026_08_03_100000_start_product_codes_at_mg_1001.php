<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Numaralandırma MG-1001'den başlasın. Sayaç sıradaki kodu last_number + 1
     * olarak ürettiği için taban 1000'dir. Katalogda halihazırda daha yüksek bir
     * numara varsa geri gitmemek için onu koruruz.
     */
    public function up(): void
    {
        $highest = DB::table('products')
            ->pluck('code')
            ->map(fn (?string $code): int => (int) preg_replace('/\D+/', '', (string) $code))
            ->max() ?? 0;

        DB::table('product_code_sequences')
            ->where('id', 1)
            ->update(['last_number' => max($highest, 1000)]);
    }

    public function down(): void
    {
        // Sayaç değeri geri alınmaz; numaraların yeniden kullanılmaması esastır.
    }
};
