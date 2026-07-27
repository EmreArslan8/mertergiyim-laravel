<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasUuidKey;

    protected $table = 'currencies';

    protected $guarded = [];

    protected $casts = ['is_default' => 'boolean', 'sort_order' => 'integer'];
}
