<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

   
    public function finance()
    {
        // ===============================
        // TODAY'S FINANCIAL DATA
        // ===============================

        $todayRevenue = Bill::whereDate('created_at', today())
            ->sum('final_amount');

        $todayPayments = Payment::whereDate('created_at', today())
            ->sum('amount');

        $todayExpenses = Expense::whereDate('expense_date', today())
            ->sum('amount');

        $todayOutstanding = Bill::whereDate('created_at', today())
            ->sum('balance');

        $todayProfit = $todayPayments - $todayExpenses;

        // ===============================
        // MONTH SUMMARY
        // ===============================

        $monthRevenue = Bill::whereMonth('created_at', now()->month)
            ->sum('final_amount');

        $monthPayments = Payment::whereMonth('created_at', now()->month)
            ->sum('amount');

        $monthExpenses = Expense::whereMonth('expense_date', now()->month)
            ->sum('amount');

        $monthProfit = $monthPayments - $monthExpenses;

        return [
            'todayRevenue' => $todayRevenue,
            'todayPayments' =>$todayPayments,
            'todayExpenses' => $todayExpenses,
            'todayOutstanding'=>$todayOutstanding,
            'todayProfit'=> $todayProfit,
            'monthRevenue' => $monthRevenue,
            'monthPayments' => $monthPayments,
            'monthExpenses' => $monthExpenses,
            'monthProfit'=>$monthProfit
        ];
    }

    public function finacialCalculation()
    {
        /* =========================
        TODAY CALCULATIONS
        ========================= */

        $todayBills = \App\Models\Bill::whereDate('created_at', today())->get();

        $todayGross = $todayBills->sum('total_amount');
        $todayDiscount = $todayBills->sum('discount_amount');

        $todayNetRevenue = $todayGross - $todayDiscount;

        $todayStaffShare = $todayNetRevenue * 0.4;
        $todayAnnexShare = $todayNetRevenue * 0.6;

        $todayExpenses = \App\Models\Expense::whereDate('expense_date', today())
                            ->sum('amount');

        $todayProfit = $todayAnnexShare - $todayExpenses;


        /* =========================
        MONTH CALCULATIONS
        ========================= */

        $monthBills = \App\Models\Bill::whereMonth('created_at', now()->month)->get();

        $monthGross = $monthBills->sum('total_amount');
        $monthDiscount = $monthBills->sum('discount_amount');

        $monthNetRevenue = $monthGross - $monthDiscount;

        $monthStaffShare = $monthNetRevenue * 0.4;
        $monthAnnexShare = $monthNetRevenue * 0.6;

        $monthExpenses = \App\Models\Expense::whereMonth('expense_date', now()->month)
                            ->sum('amount');

        $monthProfit = $monthAnnexShare - $monthExpenses;

        
        return [
            'todayGross' => $todayGross,
            'todayDiscount' => $todayDiscount,
            'todayNetRevenue' => $todayNetRevenue,
            'todayStaffShare' => $todayStaffShare,
            'todayAnnexShare' => $todayAnnexShare,
            'todayExpenses' => $todayExpenses,
            'todayProfit' => $todayProfit,
            'monthGross' => $monthGross,
            'monthDiscount' => $monthDiscount,
            'monthNetRevenue' => $monthNetRevenue,
            'monthStaffShare' => $monthStaffShare,
            'monthAnnexShare' => $monthAnnexShare,
            'monthExpenses' => $monthExpenses,
            'monthProfit' => $monthProfit,
        ];

    }
}
