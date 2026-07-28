<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'sizes', 'colors'] as $tableName) {
            DB::table($tableName)
                ->select(['id', 'name', 'name_i18n'])
                ->orderBy('id')
                ->eachById(function ($row) use ($tableName): void {
                    $localized = is_string($row->name_i18n)
                        ? json_decode($row->name_i18n, true)
                        : $row->name_i18n;
                    $localized = is_array($localized) ? $localized : [];

                    if (filled($localized['tr'] ?? null) || blank($row->name)) {
                        return;
                    }

                    $localized['tr'] = $row->name;

                    DB::table($tableName)
                        ->where('id', $row->id)
                        ->update([
                            'name_i18n' => json_encode($localized, JSON_UNESCAPED_UNICODE),
                        ]);
                }, column: 'id');
        }
    }

    public function down(): void
    {
        // Mevcut ad verisini geri silmek güvenli olmadığı için işlem yok.
    }
};
