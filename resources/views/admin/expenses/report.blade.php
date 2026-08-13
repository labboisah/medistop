@extends('layouts.app')

@section('page-title', 'Expense Report')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="text-xl font-bold text-primary">Expense Report</h2>
        <p class="text-sm text-gray-500">Report ID: {{ $reportId }}</p>
    </div>

    <div class="flex gap-3">
        <button onclick="window.print()" class="bg-primary text-white px-5 py-2 rounded-lg font-semibold">
            Print
        </button>
        <a href="{{ route('admin.expenses.download-csv', request()->query()) }}"
           class="bg-green-600 text-white px-5 py-2 rounded-lg font-semibold">
            Download CSV
        </a>
        <a href="{{ route('admin.expenses.index', request()->query()) }}"
           class="bg-gray-200 text-primary px-5 py-2 rounded-lg font-semibold">
            Back
        </a>
    </div>
</div>

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Total Expenses</p>
        <p class="text-2xl font-bold text-red-600">NGN {{ number_format($totalAmount, 2) }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Records</p>
        <p class="text-2xl font-bold text-primary">{{ $expenses->count() }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Generated On</p>
        <p class="text-2xl font-bold text-primary">{{ now()->format('d M Y') }}</p>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-bold text-primary mb-4">By Category</h3>
        <table class="w-full text-sm">
            <thead class="border-b bg-lightbg">
                <tr>
                    <th class="py-3 text-left">Category</th>
                    <th class="text-left">Count</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
            @foreach($categoryTotals as $category)
                <tr class="border-b">
                    <td class="py-3">{{ $category->category_name }}</td>
                    <td>{{ $category->count }}</td>
                    <td class="text-right text-red-600 font-semibold">NGN {{ number_format($category->total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-bold text-primary mb-4">By Recorder</h3>
        <table class="w-full text-sm">
            <thead class="border-b bg-lightbg">
                <tr>
                    <th class="py-3 text-left">User</th>
                    <th class="text-left">Count</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
            @foreach($userTotals as $user)
                <tr class="border-b">
                    <td class="py-3">{{ $user->user_name }}</td>
                    <td>{{ $user->count }}</td>
                    <td class="text-right text-red-600 font-semibold">NGN {{ number_format($user->total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
    <h3 class="font-bold text-primary mb-4">Expense Details</h3>
    <table class="w-full text-sm text-left">
        <thead class="border-b bg-lightbg">
            <tr>
                <th class="py-3">Title</th>
                <th>Category</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
        @forelse($expenses as $expense)
            <tr class="border-b">
                <td class="py-3 font-semibold">{{ $expense->title }}</td>
                <td>{{ $expense->category ?: 'Uncategorized' }}</td>
                <td>{{ $expense->description ?: '-' }}</td>
                <td class="text-red-600 font-semibold">NGN {{ number_format($expense->amount, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</td>
                <td>{{ optional($expense->user)->name ?? 'Unknown' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="py-8 text-center text-gray-500">No expenses found for this report.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
