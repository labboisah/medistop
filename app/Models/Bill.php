<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $fillable = [
        'bill_no',
        'patient_name',
        'total_amount',
        'total_staff_share',
        'total_annex_share',
        'user_id'
    ];
    
    public function items()
    {
        return $this->hasMany(BillItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
