<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class ContentPage extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'content' => 'array',
        'seo_title' => 'array',
        'seo_description' => 'array',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
