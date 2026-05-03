@extends('layouts.app')

@section('page-title', 'Edit Refund')

@section('content')

<div class="max-w-4xl mx-auto space-y-8">

    <div class="bg-white p-8 rounded-2xl shadow">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-primary">Edit Refund</h2>
                <p class="text-sm text-gray-500">Update the refund reason or amount for bill {{ $refund->bill->bill_no }}.</p>
            </div>
            <a href="{{ route('bills.show', $refund->bill) }}" class="text-sm text-primary hover:underline">← Back to bill</a>
        </div>

        <form method="POST" action="{{ route('refunds.update', $refund) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium mb-2">Refund Reason</label>
                <select name="reason" class="w-full px-4 py-3 border rounded-xl">
                    <option value="">Select reason</option>
                    @foreach($reasons as $reason)
                        <option value="{{ $reason }}" {{ old('reason', $refund->reason) === $reason ? 'selected' : '' }}>
                            {{ $reason }}
                        </option>
                    @endforeach
                </select>
                @error('reason')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Refund Amount</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount', $refund->amount) }}"
                       class="w-full px-4 py-3 border rounded-xl" />
                @error('amount')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>

            <button class="bg-accent text-white px-8 py-3 rounded-xl">Update Refund</button>
        </form>
    </div>

</div>

@endsection
