<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ürün kartındaki "5'Lİ PAKET" rozeti ve ürün detayındaki paket toplamı için
     * seri adedi. Bilgi şimdiye kadar yalnızca açıklama metninin içinde yazılıydı.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedSmallInteger('pack_size')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('pack_size');
        });
    }
};
