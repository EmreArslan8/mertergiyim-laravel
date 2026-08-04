<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = ['active' => 'boolean', 'sort_order' => 'integer', 'name_i18n' => 'array'];

    protected static function booted(): void
    {
        static::saving(function (Color $color): void {
            $color->name = $color->name_i18n['tr'] ?? $color->name;
        });

        // Sıra alanı formdan kaldırıldı (listeden sürükleyerek yönetiliyor).
        // Verilmezse hepsi 0'a yığılıp rastgele sıralanıyordu; yeni renk
        // listenin sonuna eklenir.
        static::creating(function (Color $color): void {
            if (blank($color->sort_order)) {
                $color->sort_order = ((int) static::query()->max('sort_order')) + 1;
            }
        });
    }
}
