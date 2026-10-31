<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceResourceSnapshot extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'cpu_load_percent' => 'float',
        'network_in_bytes_per_second' => 'float',
        'network_out_bytes_per_second' => 'float',
    ];
}
