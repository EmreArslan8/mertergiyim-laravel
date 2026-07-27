<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Supabase şemasındaki uuid primary key'ler için ortak ayarlar.
 */
trait HasUuidKey
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected static function bootHasUuidKey(): void
    {
        static::creating(function ($model) {
            $model->{$model->getKeyName()} ??= (string) Str::uuid();
        });
    }
}
