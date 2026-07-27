<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class TranslationUsage extends Model
{
    use HasUuidKey;

    protected $table = 'translation_usage';

    protected $guarded = [];

    protected $casts = [
        'prompt_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'ok' => 'boolean',
        'created_at' => 'datetime',
    ];
}
