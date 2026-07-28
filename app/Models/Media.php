<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasUuidKey;

    protected $table = 'media';

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'alt' => 'array',
        'caption' => 'array',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
