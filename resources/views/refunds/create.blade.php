@extends('layouts.app')

@section('page-title', 'Add Refund')

@section('content')

<div class="max-w-4xl mx-auto space-y-8">

    <div class="bg-white p-8 rounded-2xl shadow">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-primary">Add Refund</h2>
                @if($bill)
                    <p class="text-sm text-gray-500">Recording a refund for bill {{ $bill->bill_no }}.</p>
                @else
                    <p class="text-sm text-gray-500">Enter the bill number and refund details below.</p>
                @endif
            </div>
            <a href="{{ route('refunds.index') }}" class="text-sm text-primary hover:underline">← Back to refunds</a>
        </div>

        @if($bill)
            <div class="grid md:grid-cols-3 gap-6 mb-6 text-sm">
                <div>
                    <p class="text-gray-500">Final Amount</p>
                    <p class="text-lg font-bold">₦{{ number_format($bill->final_amount, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Total Paid</p>
                    <p class="text-lg font-bold text-accent">₦{{ number_format($bill->total_paid, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Already Refunded</p>
                    <p class="text-lg font-bold text-red-600">₦{{ number_format($bill->refunds->sum('amount'), 2) }}</p>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('refunds.store') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-2">Bill Number</label>
                <input type="text" name="bill_no" value="{{ old('bill_no', $billNo ?? ($bill->bill_no ?? '')) }}"
                       class="w-full px-4 py-3 border rounded-xl" placeholder="Enter bill number" />
                @error('bill_no')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Refund Reason</label>
                <select name="reason" class="w-full px-4 py-3 border rounded-xl">
                    <option value="">Select reason</option>
                    @foreach($reasons as $reason)
                        <option value="{{ $reason }}" {{ old('reason') === $reason ? 'selected' : '' }}>
                            {{ $reason }}
                        </option>
                    @endforeach
                </select>
                @error('reason')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Refund Amount</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount') }}"
                       class="w-full px-4 py-3 border rounded-xl" />
                @error('amount')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>

            <button class="bg-accent text-white px-8 py-3 rounded-xl">Save Refund</button>
        </form>
    </div>

</div>

@endsection
