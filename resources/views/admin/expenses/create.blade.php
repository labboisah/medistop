@extends('layouts.app')

@section('page-title', 'Add Admin Expense')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-8 rounded-xl shadow">
    <form method="POST" action="{{ route('admin.expenses.store') }}" class="space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Expense Title</label>
            <input type="text" name="title" value="{{ old('title') }}"
                   class="w-full px-4 py-3 border rounded-xl" required>
            @error('title') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Amount</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount') }}"
                       class="w-full px-4 py-3 border rounded-xl" required>
                @error('amount') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Expense Date</label>
                <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}"
                       class="w-full px-4 py-3 border rounded-xl" required>
                @error('expense_date') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
            <input type="text" name="category" value="{{ old('category') }}"
                   placeholder="Fuel, maintenance, supplies"
                   class="w-full px-4 py-3 border rounded-xl">
            @error('category') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
            <textarea name="description" rows="4"
                      class="w-full px-4 py-3 border rounded-xl">{{ old('description') }}</textarea>
            @error('description') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-3">
            <button class="bg-accent text-white px-6 py-3 rounded-xl font-semibold">
                Save Expense
            </button>

            <a href="{{ route('admin.expenses.index') }}"
               class="bg-gray-200 text-primary px-6 py-3 rounded-xl font-semibold">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
