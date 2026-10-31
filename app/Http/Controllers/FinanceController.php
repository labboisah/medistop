<?php

namespace App\Http\Controllers;

use App\Models\SalaryPayment;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FinanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    
    public function index(Request $request)
    {
        $selectedMonth = request('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();

        /* =========================
        TODAY CALCULATIONS
        ========================= */

        $todayStart = today()->startOfDay();
        $tomorrow = $todayStart->copy()->addDay();
        $todayTotals = \App\Models\Bill::query()
            ->where('created_at', '>=', $todayStart)
            ->where('created_at', '<', $tomorrow)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as gross, COALESCE(SUM(discount_amount), 0) as discount')
            ->first();
        $todayGross = (float) $todayTotals->gross;
        $todayDiscount = (float) $todayTotals->discount;

        $todayNetRevenue = $todayGross - $todayDiscount;

        $todayStaffShare = $todayNetRevenue * 0.4;
        $todayAnnexShare = $todayNetRevenue * 0.6;

        $todayExpenses = \App\Models\Expense::approved()->where('expense_date', today()->toDateString())
                            ->sum('amount');

        $todayProfit = $todayAnnexShare - $todayExpenses;


        /* =========================
        MONTH CALCULATIONS
        ========================= */

        $nextMonth = $monthStart->copy()->addMonth();
        $monthTotals = \App\Models\Bill::query()
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $nextMonth)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as gross, COALESCE(SUM(discount_amount), 0) as discount')
            ->first();
        $monthGross = (float) $monthTotals->gross;
        $monthDiscount = (float) $monthTotals->discount;

        $monthNetRevenue = $monthGross - $monthDiscount;

        $monthStaffShare = $monthNetRevenue * 0.4;
        $monthAnnexShare = $monthNetRevenue * 0.6;
        

        $monthExpenses = \App\Models\Expense::approved()->where('expense_date', '>=', $monthStart->toDateString())
                            ->where('expense_date', '<', $nextMonth->toDateString())
                            ->sum('amount');

        $selectedUserId = request('user_id', auth()->id());
        $salaryPayment = SalaryPayment::where('salary_month', $monthStart->toDateString())
            ->where('user_id', $selectedUserId)
            ->first();
        $monthSalaryPaid = SalaryPayment::where('salary_month', $monthStart->toDateString())->sum('amount');
        $users = User::where('role', 'user')->orderBy('name')->get();
        $monthTotalOutflow = $monthExpenses + $monthSalaryPaid;
        $monthProfit = $monthAnnexShare - $monthTotalOutflow;
        $search = trim((string) $request->query('search', ''));
        $from = $request->query('from', '');
        $to = $request->query('to', '');
        $salaryPayments = SalaryPayment::with('user')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('note', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            }))
            ->when($from !== '', fn ($query) => $query->whereDate('salary_month', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('salary_month', '<=', $to))
            ->latest('salary_month')
            ->paginate(12)
            ->withQueryString();

        $report = auth()->user()->finance();
        return view('admin.finances.index', compact(
            'todayGross',
            'todayDiscount',
            'todayNetRevenue',
            'todayStaffShare',
            'todayAnnexShare',
            'todayExpenses',
            'todayProfit',
            'monthGross',
            'monthDiscount',
            'monthNetRevenue',
            'monthStaffShare',
            'monthAnnexShare',
            'monthExpenses',
            'monthSalaryPaid',
            'monthTotalOutflow',
            'monthProfit',
            'report',
            'selectedMonth',
            'salaryPayment',
            'salaryPayments',
            'users',
            'selectedUserId',
            'search',
            'from',
            'to'
        ));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'salary_month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $salaryMonth = Carbon::createFromFormat('Y-m', $validated['salary_month'])
            ->startOfMonth()
            ->toDateString();

        $collectedAmount = Payment::where('user_id', $validated['user_id'])
            ->whereBetween('created_at', [
                Carbon::parse($salaryMonth)->startOfMonth(),
                Carbon::parse($salaryMonth)->endOfMonth(),
            ])
            ->sum('amount');

        if ((float) $collectedAmount < (float) $validated['amount']) {
            throw ValidationException::withMessages([
                'amount' => 'This user collected NGN '.number_format($collectedAmount, 2).' in the selected month, so the salary cannot exceed that amount.',
            ]);
        }

        SalaryPayment::updateOrCreate(
            ['salary_month' => $salaryMonth, 'user_id' => $validated['user_id']],
            [
                'amount' => $validated['amount'],
                'note' => $validated['note'] ?? null,
                'user_id' => $validated['user_id'],
            ]
        );

        return redirect()
            ->route('admin.finances.index', ['month' => $validated['salary_month']])
            ->with('success', 'Salary payment recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
