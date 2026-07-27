<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Supabase şemasındaki uuid primary key'ler için ortak ayarlar.
 */
trait HasUuidKey
{
    /**
     * Laravel her yeni instance'ta çağırır; property'leri burada atamak,
     * bunları trait'te yeniden tanımlayıp Model'in varsayılanlarıyla
     * (incrementing=true, keyType='int', timestamps=true) çakışmasını önler.
     */
    public function initializeHasUuidKey(): void
    {
        $this->incrementing = false;
        $this->timestamps = false;
        $this->keyType = 'string';
    }

    protected static function bootHasUuidKey(): void
    {
        static::creating(function ($model) {
            $model->{$model->getKeyName()} ??= (string) Str::uuid();
        });
    }
}
