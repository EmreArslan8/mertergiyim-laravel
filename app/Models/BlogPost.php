<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'excerpt' => 'array',
        'content' => 'array',
        'active' => 'boolean',
        'published_at' => 'datetime',
    ];
}
