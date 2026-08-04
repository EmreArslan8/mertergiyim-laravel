<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tracking_code benzersizliği yalnızca OrderCodeService'teki do...while ile
 * "en iyi çaba" olarak sağlanıyordu; veritabanı zorlamıyordu. Eşzamanlı iki
 * checkout teorik olarak aynı kodu üretebilirdi. Unique index bunu garantiye
 * çevirir; nadir çakışmada checkout'taki DB::transaction retry yeni kod üretir.
 *
 * Düz index unique ile değiştirilir (unique zaten sorgular için index görevi görür).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['tracking_code']);
            $table->unique('tracking_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['tracking_code']);
            $table->index('tracking_code');
        });
    }
};
