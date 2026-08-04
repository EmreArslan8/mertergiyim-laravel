<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telegram toptancı kanallarından çekilen ham ürün kayıtları.
 *
 * Bu tablo katalog değil, ara depodur: kanaldan gelen veri olduğu gibi durur,
 * onaylanan kayıt sonradan products tablosuna aktarılır. Kanallar farklı
 * şemalarda paylaşım yapıyor (birinde isim yok, birinde fiyat yok), bu yüzden
 * ürün alanlarının tamamı nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telegram_channel_products')) {
            Schema::create('telegram_channel_products', function (Blueprint $table) {
                $table->uuid('id')->primary();

                // Kaynak: @asprinntrendy, @naturallover, @rosearyaa
                $table->string('channel');
                // Ürünü açan mesajın id'si; albüm mesajları images tablosunda.
                $table->bigInteger('message_id');
                $table->string('post_url', 512)->nullable();
                $table->timestamp('posted_at')->nullable();

                // Kanaldaki mesaj metni, hiç dokunulmadan.
                $table->text('raw_text')->nullable();

                $table->string('name')->nullable();
                // Ürün adı nereden geldi: channel (metinden), ai (görselden), manual
                $table->string('name_source')->nullable();
                $table->string('product_code')->nullable();
                $table->string('category')->nullable();

                $table->decimal('price', 12, 2)->nullable();
                $table->string('currency', 3)->nullable();
                $table->integer('pack_size')->nullable();

                // Beden serisi hem ham ("Seri 5 li 2s 2m 1l") hem çözümlenmiş
                // ({"S":2,"M":2,"L":1}) tutulur; ham metin doğrulama için lazım.
                $table->string('size_series')->nullable();
                $table->json('sizes')->nullable();
                $table->json('colors')->nullable();

                // new → enriched → approved → imported (ya da ignored)
                $table->string('status')->default('new');
                $table->text('notes')->nullable();

                // Katalog aktarımı yapıldıysa oluşan ürün.
                $table->foreignUuid('product_id')->nullable()
                    ->constrained('products')
                    ->nullOnDelete();

                $table->timestamp('scraped_at')->nullable();
                $table->timestamps();

                // Aynı mesaj tekrar çekilirse kayıt çoğalmasın; scraper bu
                // kısıta dayanarak upsert yapar.
                $table->unique(['channel', 'message_id']);
                $table->index(['channel', 'status']);
                $table->index('posted_at');
            });
        }

        if (! Schema::hasTable('telegram_channel_product_images')) {
            Schema::create('telegram_channel_product_images', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('telegram_channel_product_id')
                    ->constrained('telegram_channel_products')
                    ->cascadeOnDelete();

                // Görselin geldiği albüm mesajı. Kanallarda her albüm çoğunlukla
                // ayrı bir renk demek, bu yüzden albüm kimliği korunuyor.
                $table->bigInteger('message_id')->nullable();
                $table->integer('album_index')->default(0);
                $table->integer('sort_order')->default(0);

                // Telegram CDN adresi. Kalıcı değil, indirildikten sonra
                // file_path dolar ve asıl kaynak o olur.
                $table->text('source_url');
                $table->string('file_path', 2048)->nullable();

                $table->timestamps();

                $table->index(['telegram_channel_product_id', 'album_index', 'sort_order'], 'tcp_images_order_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_channel_product_images');
        Schema::dropIfExists('telegram_channel_products');
    }
};
