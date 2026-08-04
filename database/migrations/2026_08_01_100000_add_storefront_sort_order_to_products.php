<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'sort_order')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->integer('sort_order')->default(0)->index();
        });

        DB::table('products')
            ->orderBy('created_at')
            ->orderBy('code')
            ->pluck('id')
            ->each(fn (string $id, int $index) => DB::table('products')
                ->where('id', $id)
                ->update(['sort_order' => $index + 1]));
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'sort_order')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });
    }
};
