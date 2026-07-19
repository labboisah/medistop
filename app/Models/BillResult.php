<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillResult extends Model
{
    protected $fillable = [
        'bill_id',
        'bill_item_id',
        'staff_id',
        'clinical_note',
        'findings',
        'impression',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function billItem()
    {
        return $this->belongsTo(BillItem::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
