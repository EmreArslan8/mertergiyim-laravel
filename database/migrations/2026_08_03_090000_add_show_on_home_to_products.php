<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ürün ana sayfada görünsün mü?
 *
 * Ana sayfa şimdiye kadar en yeni ürünleri otomatik gösteriyordu; hangi ürünün
 * çıkacağına karar verilemiyordu. Bu alan seçimi panele veriyor.
 *
 * Varsayılan true: mevcut ürünlerin tamamı bugünkü davranışla aynı kalsın,
 * ana sayfa geçişte boşalmasın. İstenmeyenler panelden kapatılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'show_on_home')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('show_on_home')->default(true)->index()->after('active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'show_on_home')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('show_on_home');
            });
        }
    }
};
