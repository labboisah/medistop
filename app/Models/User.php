<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

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
        'staff_type',
        'designation',
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

        $todayStart = today()->startOfDay();
        $tomorrow = $todayStart->copy()->addDay();
        $monthStart = now()->startOfMonth();
        $nextMonth = $monthStart->copy()->addMonth();

        $todayRevenue = Bill::where('created_at', '>=', $todayStart)
            ->where('created_at', '<', $tomorrow)
            ->sum('final_amount');

        $todayPayments = Payment::where('created_at', '>=', $todayStart)
            ->where('created_at', '<', $tomorrow)
            ->sum('amount');

        $todayExpenses = Expense::where('expense_date', today()->toDateString())
            ->sum('amount');

        $todayOutstanding = Bill::where('created_at', '>=', $todayStart)
            ->where('created_at', '<', $tomorrow)
            ->sum('balance');

        $todayProfit = $todayPayments - $todayExpenses;

        // ===============================
        // MONTH SUMMARY
        // ===============================

        $monthRevenue = Bill::where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $nextMonth)
            ->sum('final_amount');

        $monthPayments = Payment::where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $nextMonth)
            ->sum('amount');

        $monthExpenses = Expense::where('expense_date', '>=', $monthStart->toDateString())
            ->where('expense_date', '<', $nextMonth->toDateString())
            ->sum('amount');

        $monthSalaryPaid = SalaryPayment::where('salary_month', '>=', $monthStart->toDateString())
            ->where('salary_month', '<', $nextMonth->toDateString())
            ->sum('amount');

        $monthProfit = $monthPayments - ($monthExpenses + $monthSalaryPaid);

        return [
            'todayRevenue' => $todayRevenue,
            'todayPayments' =>$todayPayments,
            'todayExpenses' => $todayExpenses,
            'todayOutstanding'=>$todayOutstanding,
            'todayProfit'=> $todayProfit,
            'monthRevenue' => $monthRevenue,
            'monthPayments' => $monthPayments,
            'monthExpenses' => $monthExpenses,
            'monthSalaryPaid' => $monthSalaryPaid,
            'monthProfit'=>$monthProfit
        ];
    }

    public function finacialCalculation()
    {
        /* =========================
        TODAY CALCULATIONS
        ========================= */

        $todayStart = today()->startOfDay();
        $tomorrow = $todayStart->copy()->addDay();
        $monthStart = now()->startOfMonth();
        $nextMonth = $monthStart->copy()->addMonth();

        $todayTotals = Bill::query()
            ->where('created_at', '>=', $todayStart)
            ->where('created_at', '<', $tomorrow)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as gross, COALESCE(SUM(discount_amount), 0) as discount')
            ->first();
        $todayGross = (float) $todayTotals->gross;
        $todayDiscount = (float) $todayTotals->discount;
        $todayNetRevenue = $todayGross - $todayDiscount;

        $todayShares = $this->billSharesBetween($todayStart, $tomorrow);
        $todayStaffShare = $todayShares['staff'];
        $todayAnnexShare = $todayShares['annex'];
        $todayRadiographerShare = $todayShares['radiographer'];
        $todayRadiologistShare = $todayShares['radiologist'];

        $todayExpenses = Expense::where('expense_date', today()->toDateString())->sum('amount');

        $todayRefunds = 0;

        $todayProfit = $todayNetRevenue - ($todayExpenses + $todayRadiologistShare + $todayRadiographerShare + $todayStaffShare + $todayRefunds);


        /* =========================
        MONTH CALCULATIONS
        ========================= */

        $monthTotals = Bill::query()
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $nextMonth)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as gross, COALESCE(SUM(discount_amount), 0) as discount')
            ->first();
        $monthGross = (float) $monthTotals->gross;
        $monthDiscount = (float) $monthTotals->discount;
        $monthNetRevenue = $monthGross - $monthDiscount;
        $monthShares = $this->billSharesBetween($monthStart, $nextMonth);
        $monthStaffShare = $monthShares['staff'];
        $monthAnnexShare = $monthShares['annex'];
        $monthRadiographerShare = $monthShares['radiographer'];
        $monthRadiologistShare = $monthShares['radiologist'];

        $monthExpenses = Expense::where('expense_date', '>=', $monthStart->toDateString())
            ->where('expense_date', '<', $nextMonth->toDateString())
            ->sum('amount');

        $monthRefunds = 0;

        $monthSalaryPaid = SalaryPayment::where('salary_month', '>=', $monthStart->toDateString())
            ->where('salary_month', '<', $nextMonth->toDateString())
            ->sum('amount');

        $monthProfit = $monthAnnexShare - ($monthExpenses + $monthSalaryPaid + $monthRefunds);

        
        return [
            'todayGross' => $todayGross,
            'todayDiscount' => $todayDiscount,
            'todayNetRevenue' => $todayNetRevenue,
            'todayStaffShare' => $todayStaffShare,
            'todayAnnexShare' => $todayAnnexShare,
            'todayRadiographerShare' => $todayRadiographerShare,
            'todayRadiologistShare' => $todayRadiologistShare,
            'todayExpenses' => $todayExpenses,
            'todayRefunds' => $todayRefunds,
            'todayProfit' => $todayProfit,
            'monthGross' => $monthGross,
            'monthDiscount' => $monthDiscount,
            'monthNetRevenue' => $monthNetRevenue,
            'monthStaffShare' => $monthStaffShare,
            'monthAnnexShare' => $monthAnnexShare,
            'monthRadiographerShare' => $monthRadiographerShare,
            'monthRadiologistShare' => $monthRadiologistShare,
            'monthExpenses' => $monthExpenses,
            'monthRefunds' => $monthRefunds,
            'monthSalaryPaid' => $monthSalaryPaid,
            'monthProfit' => $monthProfit,
        ];

    }

    /**
     * Aggregate the same per-item shares used by Bill::shares without loading bills/items.
     * The current allocation formula subtracts the full bill discount from each item;
     * keep that calculation unchanged here to avoid silently changing financial reports.
     */
    private function billSharesBetween($start, $end): array
    {
        $totals = DB::table('bill_items')
            ->join('bills', 'bills.id', '=', 'bill_items.bill_id')
            ->join('services', 'services.id', '=', 'bill_items.service_id')
            ->join('categories', 'categories.id', '=', 'services.category_id')
            ->leftJoin('revenue_sharing_rules', 'revenue_sharing_rules.category_id', '=', 'categories.id')
            ->where('bills.created_at', '>=', $start)
            ->where('bills.created_at', '<', $end)
            ->selectRaw(
                'COALESCE(SUM((bill_items.price - bills.discount_amount) * COALESCE(revenue_sharing_rules.staff_percent, 0) / 100), 0) as staff, '
                . 'COALESCE(SUM((bill_items.price - bills.discount_amount) * COALESCE(revenue_sharing_rules.annex_percent, 0) / 100), 0) as annex, '
                . 'COALESCE(SUM((bill_items.price - bills.discount_amount) * COALESCE(revenue_sharing_rules.radiographer_percent, 0) / 100), 0) as radiographer, '
                . 'COALESCE(SUM((bill_items.price - bills.discount_amount) * COALESCE(revenue_sharing_rules.radiologist_percent, 0) / 100), 0) as radiologist'
            )
            ->first();

        return [
            'staff' => (float) $totals->staff,
            'annex' => (float) $totals->annex,
            'radiographer' => (float) $totals->radiographer,
            'radiologist' => (float) $totals->radiologist,
        ];
    }
}
