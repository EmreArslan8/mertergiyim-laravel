<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_code_sequences', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });

        $highest = DB::table('products')
            ->pluck('code')
            ->map(fn (?string $code): int => (int) preg_replace('/\D+/', '', (string) $code))
            ->max() ?? 0;

        DB::table('product_code_sequences')->insert([
            'id' => 1,
            'last_number' => $highest,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_code_sequences');
    }
};
