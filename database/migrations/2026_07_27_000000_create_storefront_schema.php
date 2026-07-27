<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * supabase/*.sql şemasının SQLite'a uyarlanmış hali.
 *
 * DİKKAT: Canlı veritabanı Supabase Postgres'tir ve şema orada zaten kuruludur.
 * Bu migration yalnızca lokal geliştirme/test içindir; pgsql_supabase bağlantısına
 * karşı ÇALIŞTIRILMAMALIDIR.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Güvenlik kilidi: şema zaten kuruluysa (canlı Supabase) hiçbir şey yapma.
        if (Schema::hasTable('products')) {
            return;
        }

        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('name');
            $table->text('slug')->unique();
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('sizes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('name')->unique();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
        });

        Schema::create('colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('name')->unique();
            $table->text('hex')->default('#ffffff');
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id')->nullable();
            $table->text('code')->unique();
            $table->text('slug')->unique();
            // jsonb karşılığı: SQLite'ta text, modelde array cast.
            $table->json('name');
            $table->json('description');
            $table->decimal('price', 12, 2)->nullable();
            $table->text('currency')->default('TRY');
            $table->text('stock_status')->default('in_stock');
            $table->text('video_url')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->text('storage_path');
            $table->json('alt');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('size_id')->nullable();
            $table->uuid('color_id')->nullable();
            $table->integer('stock_quantity')->default(0);

            $table->unique(['product_id', 'size_id', 'color_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('size_id')->references('id')->on('sizes')->nullOnDelete();
            $table->foreign('color_id')->references('id')->on('colors')->nullOnDelete();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('title');
            $table->text('image_path');
            $table->json('button_text');
            $table->text('button_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('order_number')->unique();
            $table->text('tracking_code')->nullable();
            $table->text('customer_name');
            $table->text('phone');
            $table->text('address')->nullable();
            $table->text('note')->nullable();
            $table->text('status')->default('new');
            $table->decimal('total', 12, 2)->nullable();
            $table->text('cargo_company')->nullable();
            $table->text('cargo_tracking_url')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->text('product_name');
            $table->text('product_code')->nullable();
            $table->text('size')->nullable();
            $table->text('color')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestampTz('created_at')->useCurrent();

            $table->index('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('code')->unique();
            $table->text('name');
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('code')->unique();
            $table->text('symbol');
            $table->text('position')->default('suffix');
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->text('key')->primary();
            $table->json('value');
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('site_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('location');
            $table->text('link_key');
            $table->json('label');
            $table->text('url');
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['location', 'link_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_links');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('hero_slides');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('sizes');
        Schema::dropIfExists('categories');
    }
};
