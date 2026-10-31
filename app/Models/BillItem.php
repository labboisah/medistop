<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\RevenueCalculator;

class BillItem extends Model
{
    protected $fillable = [
        'bill_id',
        'service_id',
        'price',
        
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function revenueDistribution() {
        return $this->hasOne(RevenueDistribution::class);
    }

    public function result()
    {
        return $this->hasOne(BillResult::class);
    }

    public function shares() {
        return RevenueCalculator::calculate($this->service, $this->finalAmount());
    }

    function finalAmount() {
        $billTotal = (float) ($this->bill->total_amount ?? 0);
        $discount = (float) ($this->bill->discount_amount ?? 0);

        if ($billTotal <= 0 || $discount <= 0) {
            return (float) $this->price;
        }

        // Allocate a bill-level discount proportionally so service shares are
        // calculated from the discounted service amount.
        return round((float) $this->price - ((float) $this->price / $billTotal * $discount), 2);
    }
}
