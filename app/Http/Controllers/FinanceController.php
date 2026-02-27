<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    
    public function index()
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
            'monthProfit',
            'report'
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
        //
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
