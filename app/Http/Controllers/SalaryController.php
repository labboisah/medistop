<?php

namespace App\Http\Controllers;

use App\Models\SalaryPayment;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $request->query('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $selectedUserId = $request->query('user_id', auth()->id());
        $salaryPayment = SalaryPayment::whereDate('salary_month', $monthStart->toDateString())
            ->where('user_id', $selectedUserId)
            ->first();

        $salaryPayments = SalaryPayment::with('user')
            ->latest('salary_month')
            ->paginate(15);

        $totalPaid = SalaryPayment::sum('amount');
        $yearPaid = SalaryPayment::whereYear('salary_month', $monthStart->year)->sum('amount');
        $monthPaid = SalaryPayment::whereDate('salary_month', $monthStart->toDateString())->sum('amount');
        $users = User::where('role', 'user')->orderBy('name')->get();

        return view('admin.salaries.index', compact(
            'selectedMonth',
            'salaryPayment',
            'salaryPayments',
            'monthPaid',
            'yearPaid',
            'totalPaid',
            'users',
            'selectedUserId'
        ));
    }

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
            ->route('admin.salaries.index', ['month' => $validated['salary_month']])
            ->with('success', 'Salary payment recorded successfully.');
    }

    public function destroy(SalaryPayment $salary)
    {
        $salary->delete();

        return redirect()
            ->route('admin.salaries.index')
            ->with('success', 'Salary payment deleted successfully.');
    }
}
