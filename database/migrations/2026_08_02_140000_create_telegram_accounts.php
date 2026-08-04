<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Çekimin hangi Telegram hesabıyla yapılacağı.
 *
 * Numara koda gömülmüyor: aynı kod başka bir kurulumda kendi numarasıyla
 * çalışsın diye panelden giriliyor. Hesap seçilmeden başlatılan tarama
 * bugünkü hesapsız yoldan (t.me/s/ önizlemesi) devam ettiği için
 * telegram_scans.telegram_account_id nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telegram_accounts')) {
            Schema::create('telegram_accounts', function (Blueprint $table) {
                $table->uuid('id')->primary();

                // Panelde ayırt etmek için: "Merter ana hat".
                $table->string('label')->nullable();
                // Uluslararası biçimde saklanır: +905060603884
                $table->string('phone')->unique();

                // my.telegram.org → API development tools
                $table->string('api_id')->nullable();
                // Uygulama parolası niteliğinde; encrypted cast ile şifreli yazılır.
                $table->text('api_hash')->nullable();

                // MadelineProto oturum klasörü (storage/app/private altında).
                // Oturum dosyası hesaba tam erişim demek, repoya girmez.
                $table->string('session_path')->nullable();

                // new → awaiting_code → active (ya da failed)
                $table->string('status')->default('new');
                $table->text('last_error')->nullable();
                $table->timestamp('last_used_at')->nullable();

                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('telegram_scans') && ! Schema::hasColumn('telegram_scans', 'telegram_account_id')) {
            Schema::table('telegram_scans', function (Blueprint $table) {
                // Hesap silinse bile tarama geçmişi durur.
                $table->foreignUuid('telegram_account_id')->nullable()->after('channels')
                    ->constrained('telegram_accounts')
                    ->nullOnDelete();

                // preview = hesapsız önizleme (600×800, sıkıştırılmış video)
                // mtproto = hesapla çekim (orijinal foto + video)
                $table->string('source')->default('preview')->after('telegram_account_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('telegram_scans') && Schema::hasColumn('telegram_scans', 'telegram_account_id')) {
            Schema::table('telegram_scans', function (Blueprint $table) {
                $table->dropConstrainedForeignId('telegram_account_id');
                $table->dropColumn('source');
            });
        }

        Schema::dropIfExists('telegram_accounts');
    }
};
