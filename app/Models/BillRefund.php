<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillRefund extends Model
{
    protected $fillable = [
        'bill_id',
        'user_id',
        'amount',
        'reason',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
