<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Panel içi not: müşteri notundan (orders.note) ayrı, yalnızca ekip görür.
 * "Hediye paketi yapılacak", "müşteri aradı, adres değişti" gibi işlemler
 * şimdiye kadar müşteri notunun içine yazılıyordu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'internal_note')) {
                $table->text('internal_note')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'internal_note')) {
                $table->dropColumn('internal_note');
            }
        });
    }
};
