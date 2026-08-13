@extends('layouts.app')

@section('page-title', 'Admin Expenses')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="text-xl font-bold text-primary">Expense Ledger</h2>
        <p class="text-sm text-gray-500">Create, search, and report all operational expenses.</p>
    </div>

    <a href="{{ route('admin.expenses.create') }}"
       class="bg-accent text-white px-5 py-2 rounded-lg font-semibold">
        + Add Expense
    </a>
</div>

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Filtered Total</p>
        <p class="text-2xl font-bold text-red-600">NGN {{ number_format($totalAmount, 2) }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Today's Total Expense</p>
        <p class="text-2xl font-bold text-red-600">NGN {{ number_format($todayTotal, 2) }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Matching Records</p>
        <p class="text-2xl font-bold text-primary">{{ $expenses->total() }}</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-6 mb-6">
    <form method="GET" action="{{ route('admin.expenses.index') }}" class="grid md:grid-cols-5 gap-4">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search title, category, user"
               class="px-4 py-3 border rounded-xl md:col-span-2">

        <input type="date" name="from" value="{{ request('from') }}"
               class="px-4 py-3 border rounded-xl">

        <input type="date" name="to" value="{{ request('to') }}"
               class="px-4 py-3 border rounded-xl">

        <select name="category" class="px-4 py-3 border rounded-xl">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                    {{ $category }}
                </option>
            @endforeach
        </select>

        <select name="user_id" class="px-4 py-3 border rounded-xl">
            <option value="">All users</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>

        <div class="md:col-span-4 flex flex-wrap gap-3">
            <button class="bg-primary text-white px-5 py-3 rounded-xl font-semibold">
                Search
            </button>

            <a href="{{ route('admin.expenses.index') }}"
               class="bg-gray-200 text-primary px-5 py-3 rounded-xl font-semibold">
                Reset
            </a>

            <a href="{{ route('admin.expenses.report', request()->query()) }}"
               class="bg-indigo-600 text-white px-5 py-3 rounded-xl font-semibold">
                Generate Report
            </a>

            <a href="{{ route('admin.expenses.download-csv', request()->query()) }}"
               class="bg-green-600 text-white px-5 py-3 rounded-xl font-semibold">
                Download CSV
            </a>
        </div>
    </form>
</div>

@if($categoryTotals->isNotEmpty())
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="font-bold text-primary mb-4">Category Summary</h3>
        <div class="grid md:grid-cols-3 gap-4">
            @foreach($categoryTotals as $category)
                <div class="border rounded-xl p-4">
                    <p class="text-sm text-gray-500">{{ $category->category_name }}</p>
                    <p class="text-lg font-bold text-red-600">NGN {{ number_format($category->total, 2) }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
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
            <tr class="border-b hover:bg-lightbg">
                <td class="py-3 font-semibold">{{ $expense->title }}</td>
                <td>{{ $expense->category ?: 'Uncategorized' }}</td>
                <td class="max-w-xs truncate">{{ $expense->description ?: '-' }}</td>
                <td class="text-red-600 font-semibold">NGN {{ number_format($expense->amount, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</td>
                <td>{{ optional($expense->user)->name ?? 'Unknown' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="py-8 text-center text-gray-500">No expenses found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-6">
        {{ $expenses->links() }}
    </div>
</div>
@endsection
