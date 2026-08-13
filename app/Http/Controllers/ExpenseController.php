<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::latest()->paginate(15);

        $todayTotal = Expense::whereDate('expense_date', today())
            ->sum('amount');

        return view('expenses.index', compact('expenses','todayTotal'));
    }

    public function create()
    {
        return view('expenses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'expense_date' => 'required|date'
        ]);

        Expense::create([
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'category' => $request->category,
            'expense_date' => $request->expense_date,
            'user_id' => auth()->id()
        ]);

        return redirect()
            ->route('expenses.index')
            ->with('success','Expense recorded successfully.');
    }

    public function edit(Expense $expense)
    {
        return view('expenses.edit', compact('expense'));
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'expense_date' => 'required|date'
        ]);

        $expense->update($request->all());

        return redirect()
            ->route('expenses.index')
            ->with('success','Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()
            ->route('expenses.index')
            ->with('success','Expense deleted successfully.');
    }

    public function adminIndex(Request $request)
    {
        $query = $this->adminExpenseQuery($request);

        $expenses = (clone $query)
            ->latest('expense_date')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $totalAmount = (clone $query)->sum('amount');
        $todayTotal = Expense::whereDate('expense_date', today())->sum('amount');
        $categoryTotals = (clone $query)
            ->selectRaw('COALESCE(category, "Uncategorized") as category_name, SUM(amount) as total')
            ->groupBy('category_name')
            ->orderByDesc('total')
            ->get();
        $users = User::orderBy('name')->get();
        $categories = Expense::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin.expenses.index', compact(
            'expenses',
            'totalAmount',
            'todayTotal',
            'categoryTotals',
            'users',
            'categories'
        ));
    }

    public function adminCreate()
    {
        return view('admin.expenses.create');
    }

    public function adminStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'expense_date' => 'required|date',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Expense::create([
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'category' => $request->category,
            'expense_date' => $request->expense_date,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    public function adminReport(Request $request)
    {
        $query = $this->adminExpenseQuery($request);

        $expenses = (clone $query)
            ->latest('expense_date')
            ->latest()
            ->get();

        $categoryTotals = (clone $query)
            ->selectRaw('COALESCE(category, "Uncategorized") as category_name, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_name')
            ->orderByDesc('total')
            ->get();

        $userTotals = (clone $query)
            ->join('users', 'expenses.user_id', '=', 'users.id')
            ->selectRaw('users.name as user_name, SUM(expenses.amount) as total, COUNT(*) as count')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->get();

        $totalAmount = $expenses->sum('amount');
        $reportId = 'EXP-' . now()->format('YmdHis');

        return view('admin.expenses.report', compact(
            'expenses',
            'categoryTotals',
            'userTotals',
            'totalAmount',
            'reportId'
        ));
    }

    public function adminDownloadCsv(Request $request)
    {
        $expenses = $this->adminExpenseQuery($request)
            ->latest('expense_date')
            ->latest()
            ->get();

        $filename = 'expense-report-' . now()->format('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($expenses) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Title', 'Category', 'Description', 'Amount', 'Expense Date', 'Recorded By', 'Created At']);

            foreach ($expenses as $expense) {
                fputcsv($file, [
                    $expense->title,
                    $expense->category ?: 'Uncategorized',
                    $expense->description,
                    $expense->amount,
                    $expense->expense_date,
                    optional($expense->user)->name,
                    optional($expense->created_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function adminExpenseQuery(Request $request)
    {
        return Expense::with('user')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('from'), function ($query) use ($request) {
                $query->whereDate('expense_date', '>=', $request->from);
            })
            ->when($request->filled('to'), function ($query) use ($request) {
                $query->whereDate('expense_date', '<=', $request->to);
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category', $request->category);
            })
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $query->where('user_id', $request->user_id);
            });
    }
}
