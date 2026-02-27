<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];
    
    public function bill() : Returntype {
        return $this->belongsTo(Bill::class);
    }
}
