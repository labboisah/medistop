<?php

namespace App\Exports\Sheets;

use App\Models\Bill;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DetailedBills implements FromCollection, WithHeadings
{
    protected $from;
    protected $to;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function collection()
    {
        return Bill::whereBetween('created_at', [$this->from,$this->to])
            ->get()
            ->map(function($bill) {
                return [
                    'Bill No' => $bill->bill_no,
                    'Date' => $bill->created_at->format('d M Y'),
                    'Total' => $bill->total_amount,
                    'Discount' => $bill->discount_amount,
                    'Net' => $bill->total_amount - $bill->discount_amount
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Bill No',
            'Date',
            'Total',
            'Discount',
            'Net'
        ];
    }
}