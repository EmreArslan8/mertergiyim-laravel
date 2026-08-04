<?php

use App\Support\ProductName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mükerrer ürün adını veritabanı seviyesinde engeller.
 *
 * Panel kontrolü tek başına yetmez: iki sekmede eşzamanlı kayıt, toplu içe
 * aktarma veya doğrudan yazılan kod formu atlar. Benzersiz name_key bu yolların
 * hepsini kapatır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('name_key')->nullable()->after('slug');
        });

        $this->backfill();

        Schema::table('products', function (Blueprint $table): void {
            $table->unique('name_key');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique(['name_key']);
            $table->dropColumn('name_key');
        });
    }

    /**
     * Mevcut kayıtların anahtarını üretir. Zaten mükerrer ad varsa kısıt
     * kurulamayacağı için sonraki kayıtların anahtarına ürün kodu eklenir;
     * kayıtlar korunur, birleştirme panelden elle yapılır.
     */
    private function backfill(): void
    {
        $used = [];

        foreach (DB::table('products')->select('id', 'code', 'name')->orderBy('created_at')->get() as $product) {
            $name = json_decode((string) $product->name, true);
            $key = ProductName::key(is_array($name) ? $name : (string) $product->name);

            if ($key === '') {
                $key = ProductName::key((string) $product->code);
            }

            if (isset($used[$key])) {
                $key .= '-'.ProductName::key((string) $product->code);
            }

            $used[$key] = true;

            DB::table('products')->where('id', $product->id)->update(['name_key' => $key]);
        }
    }
};
