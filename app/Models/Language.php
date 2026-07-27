<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = ['active' => 'boolean', 'sort_order' => 'integer'];
}
