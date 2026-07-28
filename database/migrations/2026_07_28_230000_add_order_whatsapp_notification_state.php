<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sipariş bildiriminin WhatsApp'a gerçekten gidip gitmediği panelde görünsün;
 * gitmeyenler elle tekrar denenebilsin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestampTz('whatsapp_notified_at')->nullable();
            $table->text('whatsapp_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_notified_at', 'whatsapp_error']);
        });
    }
};
