<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class SiteLink extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        'label' => 'array',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
