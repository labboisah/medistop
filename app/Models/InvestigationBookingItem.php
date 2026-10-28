<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestigationBookingItem extends Model
{
    protected $fillable = ['booking_id', 'service_id', 'price'];

    public function booking()
    {
        return $this->belongsTo(InvestigationBooking::class, 'booking_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
