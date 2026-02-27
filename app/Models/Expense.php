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
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
