<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = [
        'read' => 'boolean',
    ];
}
