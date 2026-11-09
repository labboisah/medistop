<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionClaim extends Model
{
    protected $fillable = [
        'staff_id', 'approver_id', 'commission_date', 'settled_by', 'requested_amount', 'settled_amount',
        'status', 'staff_note', 'settlement_note', 'settled_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'settled_amount' => 'decimal:2',
        'settled_at' => 'datetime',
        'commission_date' => 'date',
    ];

    public function staff() { return $this->belongsTo(User::class, 'staff_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approver_id'); }
    public function settler() { return $this->belongsTo(User::class, 'settled_by'); }
}
