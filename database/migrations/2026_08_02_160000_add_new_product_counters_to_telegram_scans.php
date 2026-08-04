<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Bu taramada kaç ürün YENİ?" sorusunun cevabı.
 *
 * Tarama her seferinde kanalın son N mesajını baştan okuyor, bu yüzden aynı
 * ürünler tekrar tekrar geliyor. Kayıt çoğalmıyor (channel + message_id
 * benzersiz) ama panel "47 ürün bulundu" derken 35'i zaten duruyor olabiliyor
 * ve kullanıcı her seferinde tanıdık kartları gözle eliyor.
 *
 * Çözüm: ürünün İLK görüldüğü tarama ayrıca saklanıyor. Böylece bir taramanın
 * detayında yalnızca o taramada ortaya çıkanlar listelenebiliyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telegram_scans')) {
            Schema::table('telegram_scans', function (Blueprint $table) {
                if (! Schema::hasColumn('telegram_scans', 'new_count')) {
                    // found_count = işlenen toplam, new_count = ilk kez görülen.
                    // Mevcut = found_count - new_count.
                    $table->integer('new_count')->default(0)->after('found_count');
                }

                if (! Schema::hasColumn('telegram_scans', 'changed_count')) {
                    // Metni önceki taramadakinden farklı olanlar: kanal postu
                    // düzenlemiş demek (fiyat/isim güncellemesi).
                    $table->integer('changed_count')->default(0)->after('new_count');
                }

                if (! Schema::hasColumn('telegram_scans', 'cursor')) {
                    // Sıradaki kanalın sırası. Panel taramayı kanal kanal
                    // ilerletiyor (her istek bir kanal), ilerleme çubuğu ve
                    // "2/3 kanal" bilgisi buradan geliyor.
                    $table->integer('cursor')->default(0)->after('changed_count');
                }
            });
        }

        if (Schema::hasTable('telegram_channel_products')) {
            Schema::table('telegram_channel_products', function (Blueprint $table) {
                if (! Schema::hasColumn('telegram_channel_products', 'first_telegram_scan_id')) {
                    // telegram_scan_id her taramada güncelleniyor (en son nerede
                    // görüldü); bu ise hiç değişmiyor (ilk nerede çıktı).
                    $table->foreignUuid('first_telegram_scan_id')->nullable()->after('telegram_scan_id')
                        ->constrained('telegram_scans')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('telegram_channel_products', 'source_changed_at')) {
                    $table->timestamp('source_changed_at')->nullable()->after('scraped_at');
                }
            });

            // Eski kayıtlar: ilk görüldükleri tarama bilinmiyor, en son
            // görüldükleri tarama en yakın doğru değer.
            DB::table('telegram_channel_products')
                ->whereNull('first_telegram_scan_id')
                ->whereNotNull('telegram_scan_id')
                ->update(['first_telegram_scan_id' => DB::raw('telegram_scan_id')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('telegram_channel_products')) {
            Schema::table('telegram_channel_products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('first_telegram_scan_id');
                $table->dropColumn('source_changed_at');
            });
        }

        if (Schema::hasTable('telegram_scans')) {
            Schema::table('telegram_scans', function (Blueprint $table) {
                $table->dropColumn(['new_count', 'changed_count']);
            });
        }
    }
};
