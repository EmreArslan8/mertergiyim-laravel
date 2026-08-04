<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori sırası vitrindeki sekmeleri belirler; panelden sürükle-bırak ile
 * ayarlanabilmesi için sort_order eklenir. Mevcut kayıtlar 0'da başlar,
 * ilk sıralamada gerçek değerleri alır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->integer('sort_order')->default(0)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });
    }
};
