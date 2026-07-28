<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sepet formu her açılışta tek kullanımlık bir anahtar taşır. Çift tıklama veya
 * sayfa yenileme aynı anahtarla geldiğinde ikinci sipariş oluşturulmaz, mevcut
 * siparişin takip sayfasına yönlendirilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
