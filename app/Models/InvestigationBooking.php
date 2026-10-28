<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestigationBooking extends Model
{
    protected $fillable = [
        'reference', 'patient_name', 'phone', 'gender', 'age', 'status', 'bill_id', 'accepted_by',
    ];

    public function items()
    {
        return $this->hasMany(InvestigationBookingItem::class, 'booking_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }
}
