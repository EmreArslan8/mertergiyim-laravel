<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'button_text' => 'array',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
