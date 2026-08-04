<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Kanal yönetimi + tarama geçmişi, bir de medya tablosuna video desteği.
 *
 * Kanallar panelden eklenip çıkarılabilsin diye sabit listeden tabloya taşındı.
 */
return new class extends Migration
{
    /** Modül ilk açıldığında hazır gelen kanallar. */
    private const SEED_CHANNELS = [
        ['username' => 'asprinntrendy', 'title' => 'AsprinTrendy'],
        ['username' => 'naturallover', 'title' => 'Natural Love'],
        ['username' => 'rosearyaa', 'title' => 'RoseArya'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('telegram_channels')) {
            Schema::create('telegram_channels', function (Blueprint $table) {
                $table->uuid('id')->primary();
                // @ olmadan saklanır: 'naturallover'
                $table->string('username')->unique();
                $table->string('title')->nullable();
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(0);
                // Son taramada nereye kadar inildi; artımlı tarama için.
                $table->bigInteger('last_scanned_message_id')->nullable();
                $table->timestamp('last_scanned_at')->nullable();
                $table->timestamps();
            });

            foreach (self::SEED_CHANNELS as $index => $channel) {
                DB::table('telegram_channels')->insert([
                    'id' => (string) Str::uuid(),
                    'username' => $channel['username'],
                    'title' => $channel['title'],
                    'active' => true,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (! Schema::hasTable('telegram_scans')) {
            Schema::create('telegram_scans', function (Blueprint $table) {
                $table->uuid('id')->primary();
                // Panelde "#7" diye görünen sıra numarası. uuid birincil anahtar
                // olduğu için auto-increment kullanılamıyor; TelegramScan modeli
                // kayıt oluştururken sıradaki numarayı atıyor.
                $table->unsignedBigInteger('number')->unique();
                // Tek taramada birden çok kanal seçilebiliyor.
                $table->json('channels');
                $table->integer('message_limit')->default(100);

                // queued → running → completed / failed
                $table->string('status')->default('queued');
                // Kullanıcıya gösterilen son durum satırı ("Tarama bitti.")
                $table->string('message')->nullable();
                $table->text('error')->nullable();

                $table->integer('found_count')->default(0);
                $table->integer('imported_count')->default(0);

                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();

                $table->index('status');
            });
        }

        // Ürünler hangi taramadan geldi?
        if (Schema::hasTable('telegram_channel_products') && ! Schema::hasColumn('telegram_channel_products', 'telegram_scan_id')) {
            Schema::table('telegram_channel_products', function (Blueprint $table) {
                $table->foreignUuid('telegram_scan_id')->nullable()->after('id')
                    ->constrained('telegram_scans')
                    ->nullOnDelete();
            });
        }

        // Medya tablosu artık video da tutuyor.
        if (Schema::hasTable('telegram_channel_product_images')) {
            Schema::table('telegram_channel_product_images', function (Blueprint $table) {
                if (! Schema::hasColumn('telegram_channel_product_images', 'type')) {
                    $table->string('type')->default('photo')->after('telegram_channel_product_id');
                }
                if (! Schema::hasColumn('telegram_channel_product_images', 'poster_url')) {
                    // Videonun kapak karesi. 20 MB üstü videolarda Telegram
                    // dosyayı vermiyor ama kapak karesi her zaman geliyor.
                    $table->text('poster_url')->nullable()->after('source_url');
                }
                if (! Schema::hasColumn('telegram_channel_product_images', 'duration')) {
                    $table->string('duration', 16)->nullable()->after('poster_url');
                }
                if (! Schema::hasColumn('telegram_channel_product_images', 'downloadable')) {
                    // false = Telegram "Media is too big" diyor, dosya adresi yok.
                    $table->boolean('downloadable')->default(true)->after('duration');
                }

                // İndirilemeyen videoda elimizde yalnızca kapak karesi kalıyor,
                // bu yüzden source_url artık boş olabilmeli.
                $table->text('source_url')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('telegram_channel_products') && Schema::hasColumn('telegram_channel_products', 'telegram_scan_id')) {
            Schema::table('telegram_channel_products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('telegram_scan_id');
            });
        }

        if (Schema::hasTable('telegram_channel_product_images')) {
            Schema::table('telegram_channel_product_images', function (Blueprint $table) {
                $table->dropColumn(['type', 'poster_url', 'duration', 'downloadable']);
            });
        }

        Schema::dropIfExists('telegram_scans');
        Schema::dropIfExists('telegram_channels');
    }
};
