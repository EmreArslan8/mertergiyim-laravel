<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateSettings(function (array $value): array {
            if (data_get($value, 'general.homeProductLimit') === null) {
                data_set($value, 'general.homeProductLimit', 12);
            }

            return $value;
        });
    }

    public function down(): void
    {
        $this->updateSettings(function (array $value): array {
            data_forget($value, 'general.homeProductLimit');

            return $value;
        });
    }

    private function updateSettings(callable $callback): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $raw = DB::table('site_settings')->where('key', 'storefront')->value('value');
        $value = is_array($raw) ? $raw : json_decode((string) $raw, true);

        if (! is_array($value)) {
            return;
        }

        DB::table('site_settings')->where('key', 'storefront')->update([
            'value' => json_encode($callback($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }
};
