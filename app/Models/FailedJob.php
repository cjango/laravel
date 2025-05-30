<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    public $timestamps = false;

    protected $casts = [
        'payload' => 'json',
        'failed_at' => 'datetime',
    ];
}
