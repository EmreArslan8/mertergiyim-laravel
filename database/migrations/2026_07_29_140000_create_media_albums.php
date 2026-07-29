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
        if (! Schema::hasTable('media_posts')) {
            Schema::create('media_posts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('legacy_media_id')->nullable()->unique();
                $table->json('title')->nullable();
                $table->json('description')->nullable();
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['active', 'sort_order']);
            });
        }

        if (! Schema::hasTable('media_files')) {
            Schema::create('media_files', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('media_post_id')
                    ->constrained('media_posts')
                    ->cascadeOnDelete();
                $table->uuid('legacy_media_id')->nullable()->unique();
                $table->string('type')->default('image');
                $table->string('file_path', 2048);
                $table->json('alt')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['media_post_id', 'sort_order']);
            });
        }

        // Eski tekil kayıtları silmeden birer tek dosyalı albüme dönüştür.
        if (! Schema::hasTable('media')) {
            return;
        }

        DB::table('media')
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get()
            ->each(function (object $media): void {
                $existingPostId = DB::table('media_posts')
                    ->where('legacy_media_id', $media->id)
                    ->value('id');

                $postId = $existingPostId ?: (string) Str::uuid();

                if (! $existingPostId) {
                    DB::table('media_posts')->insert([
                        'id' => $postId,
                        'legacy_media_id' => $media->id,
                        'title' => $media->title,
                        'description' => $media->caption,
                        'active' => $media->active,
                        'sort_order' => $media->sort_order,
                        'created_at' => $media->created_at,
                        'updated_at' => $media->updated_at,
                    ]);
                }

                if (! DB::table('media_files')->where('legacy_media_id', $media->id)->exists()) {
                    DB::table('media_files')->insert([
                        'id' => (string) Str::uuid(),
                        'media_post_id' => $postId,
                        'legacy_media_id' => $media->id,
                        'type' => $media->type,
                        'file_path' => $media->file_path,
                        'alt' => $media->alt,
                        'sort_order' => 0,
                        'created_at' => $media->created_at,
                        'updated_at' => $media->updated_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('media_posts');
    }
};
