<?php

namespace App\Models\Concerns;

/**
 * Sıralanabilir kayıtlar için otomatik sıra no.
 *
 * Yeni kayıtta sort_order verilmemişse listenin sonuna eklenir (mevcut en
 * büyük + 1). Manuel sıralama (Filament tablosunda sürükle-bırak) sonradan
 * sort_order'ı günceller. Panelde ayrı bir "Sıra" girişi gerekmez.
 *
 * Eloquent, trait adına göre bu boot metodunu otomatik çağırır; modelin kendi
 * booted() metoduyla çakışmaz.
 */
trait HasAutoSortOrder
{
    protected static function bootHasAutoSortOrder(): void
    {
        static::creating(function ($model): void {
            if (blank($model->sort_order)) {
                $model->sort_order = (int) static::max('sort_order') + 1;
            }
        });
    }
}
