<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaPost extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(MediaFile::class)->orderBy('sort_order');
    }
}
