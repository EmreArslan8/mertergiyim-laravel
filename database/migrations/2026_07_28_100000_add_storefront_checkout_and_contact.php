<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'currency')) {
                $table->string('currency', 3)->default('TRY');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'product_id')) {
                $table->uuid('product_id')->nullable();
            }
            if (! Schema::hasColumn('order_items', 'variant_id')) {
                $table->uuid('variant_id')->nullable();
            }
            if (! Schema::hasColumn('order_items', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('order_items', 'line_total')) {
                $table->decimal('line_total', 12, 2)->nullable();
            }
        });

        if (! Schema::hasTable('contact_messages')) {
            Schema::create('contact_messages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('phone');
                $table->string('email')->nullable();
                $table->string('subject')->nullable();
                $table->text('message');
                $table->string('locale', 5)->default('tr');
                $table->boolean('read')->default(false);
                $table->timestampsTz();
            });
        }

        $links = [
            ['blog', '/blog', ['tr' => 'Blog', 'en' => 'Blog', 'ar' => 'المدونة', 'ru' => 'Блог', 'fa' => 'وبلاگ', 'uk' => 'Блог', 'fr' => 'Blog', 'de' => 'Blog', 'es' => 'Blog', 'it' => 'Blog'], 40],
            ['multimedia', '/multimedya', ['tr' => 'Multimedya', 'en' => 'Media', 'ar' => 'الوسائط', 'ru' => 'Медиа', 'fa' => 'رسانه', 'uk' => 'Медіа', 'fr' => 'Médias', 'de' => 'Medien', 'es' => 'Multimedia', 'it' => 'Media'], 50],
            ['contact', '/iletisim', ['tr' => 'İletişim', 'en' => 'Contact', 'ar' => 'اتصل بنا', 'ru' => 'Контакты', 'fa' => 'تماس', 'uk' => 'Контакти', 'fr' => 'Contact', 'de' => 'Kontakt', 'es' => 'Contacto', 'it' => 'Contatti'], 60],
            ['cart', '/sepet', ['tr' => 'Sepet', 'en' => 'Cart', 'ar' => 'السلة', 'ru' => 'Корзина', 'fa' => 'سبد', 'uk' => 'Кошик', 'fr' => 'Panier', 'de' => 'Warenkorb', 'es' => 'Carrito', 'it' => 'Carrello'], 70],
        ];

        foreach ($links as [$key, $url, $label, $sort]) {
            if (! DB::table('site_links')->where('location', 'header')->where('link_key', $key)->exists()) {
                DB::table('site_links')->insert([
                    'id' => (string) Str::uuid(),
                    'location' => 'header',
                    'link_key' => $key,
                    'label' => json_encode($label, JSON_UNESCAPED_UNICODE),
                    'url' => $url,
                    'sort_order' => $sort,
                    'active' => true,
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');

        Schema::table('order_items', function (Blueprint $table) {
            foreach (['product_id', 'variant_id', 'unit_price', 'line_total'] as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};
