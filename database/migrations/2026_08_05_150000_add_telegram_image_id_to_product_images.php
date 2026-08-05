<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            // Hızlı Ekle'de aynı Telegram görseli ikinci kez aktarılınca
            // ürüne tekrar bağlanmaması için kaynak görselin kimliği tutulur.
            $table->uuid('telegram_image_id')->nullable()->after('storage_path');
            $table->index(['product_id', 'telegram_image_id'], 'product_images_telegram_src_index');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex('product_images_telegram_src_index');
            $table->dropColumn('telegram_image_id');
        });
    }
};
