<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sipariş takibi tracking_code üzerinden sorgulanıyor ama sütunun indeksi yoktu;
 * order_number zaten unique. Sipariş sayısı arttıkça takip sorgusu yavaşlamasın.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('tracking_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tracking_code']);
        });
    }
};
