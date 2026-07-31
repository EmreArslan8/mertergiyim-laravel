<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sipariş bildirimi WhatsApp'tan Telegram'a taşındı; bildirim durum kolonları
 * da yeni adlarını alsın (mevcut veriyi koruyarak).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'whatsapp_notified_at')) {
                $table->renameColumn('whatsapp_notified_at', 'telegram_notified_at');
            }

            if (Schema::hasColumn('orders', 'whatsapp_error')) {
                $table->renameColumn('whatsapp_error', 'telegram_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'telegram_notified_at')) {
                $table->renameColumn('telegram_notified_at', 'whatsapp_notified_at');
            }

            if (Schema::hasColumn('orders', 'telegram_error')) {
                $table->renameColumn('telegram_error', 'whatsapp_error');
            }
        });
    }
};
