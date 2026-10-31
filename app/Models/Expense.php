<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'title',
        'description',
        'amount',
        'category',
        'expense_date',
        'user_id',
        'approval_status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = ['approved_at' => 'datetime'];

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
