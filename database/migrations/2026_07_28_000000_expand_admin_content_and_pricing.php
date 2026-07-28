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
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'price_try')) {
                $table->decimal('price_try', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('products', 'price_usd')) {
                $table->decimal('price_usd', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('products', 'price_eur')) {
                $table->decimal('price_eur', 12, 2)->nullable();
            }
        });

        foreach (['categories', 'sizes', 'colors'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'name_i18n')) {
                    $table->json('name_i18n')->nullable();
                }
            });

            DB::table($tableName)
                ->whereNull('name_i18n')
                ->orderBy('id')
                ->eachById(fn ($row) => DB::table($tableName)
                    ->where('id', $row->id)
                    ->update(['name_i18n' => json_encode(['tr' => $row->name], JSON_UNESCAPED_UNICODE)]),
                    column: 'id');
        }

        DB::table('products')
            ->whereNull('price_try')
            ->orderBy('id')
            ->eachById(function ($row): void {
                DB::table('products')->where('id', $row->id)->update([
                    'price_try' => $row->price,
                    'price_usd' => $row->price,
                    'price_eur' => $row->price,
                ]);
            }, column: 'id');

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('editor');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        if ($firstUserId) {
            DB::table('users')->where('id', $firstUserId)->update(['role' => 'super_admin', 'is_active' => true]);
        }

        if (! Schema::hasTable('content_pages')) {
            Schema::create('content_pages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('slug')->unique();
                $table->json('title');
                $table->json('content');
                $table->json('seo_title')->nullable();
                $table->json('seo_description')->nullable();
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestampsTz();
            });
        }

        if (! Schema::hasTable('blog_posts')) {
            Schema::create('blog_posts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('slug')->unique();
                $table->json('title');
                $table->json('excerpt')->nullable();
                $table->json('content');
                $table->string('cover_image')->nullable();
                $table->boolean('active')->default(true);
                $table->timestampTz('published_at')->nullable();
                $table->timestampsTz();
            });
        }

        if (! Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type')->default('image');
                $table->string('file_path');
                $table->json('title')->nullable();
                $table->json('alt')->nullable();
                $table->json('caption')->nullable();
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestampsTz();
            });
        }

        if (! Schema::hasTable('translation_usage')) {
            Schema::create('translation_usage', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('model')->nullable();
                $table->unsignedInteger('prompt_tokens')->default(0);
                $table->unsignedInteger('output_tokens')->default(0);
                $table->unsignedInteger('total_tokens')->default(0);
                $table->boolean('ok')->default(true);
                $table->text('error')->nullable();
                $table->timestampTz('created_at')->useCurrent();
            });
        }

        foreach ([
            ['TRY', 'TL', 'suffix', true, 1],
            ['USD', '$', 'prefix', false, 2],
            ['EUR', '€', 'suffix', false, 3],
        ] as [$code, $symbol, $position, $default, $sort]) {
            if (! DB::table('currencies')->where('code', $code)->exists()) {
                DB::table('currencies')->insert([
                    'id' => (string) Str::uuid(),
                    'code' => $code,
                    'symbol' => $symbol,
                    'position' => $position,
                    'is_default' => $default,
                    'sort_order' => $sort,
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
        Schema::dropIfExists('translation_usage');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('content_pages');
    }
};
