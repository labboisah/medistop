@extends('layouts.app')

@section('page-title', 'Bill Details')

@section('content')

<div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl shadow">

    <div class="flex justify-between mb-6">
        <h2 class="text-2xl font-bold text-primary">
            Bill: {{ $bill->bill_no }}
        </h2>

        <a href="{{ route('bills.index') }}"
           class="text-sm text-primary hover:underline">
            ← Back
        </a>
        <div class="flex items-center gap-3">
            @if($bill->balance > 0)
                <a href="{{ route('payments.create', $bill) }}"
                class="bg-accent text-white px-6 py-3 rounded-xl">
                    Record Payment
                </a>
            @endif
            <a href="{{ route('refunds.create', ['bill_no' => $bill->bill_no]) }}"
               class="bg-red-600 text-white px-6 py-3 rounded-xl hover:bg-red-700 transition">
                Add Refund
            </a>
        </div>
    </div>

    <div class="mb-6 text-sm text-gray-600">
        <p><strong>Patient:</strong> {{ $bill->patient_name ?? 'Walk-in' }}</p>
        <p><strong>Recorded By:</strong> {{ $bill->user->name }}</p>
        <p><strong>Date:</strong> {{ $bill->created_at->format('d M Y h:i A') }}</p>
    </div>

    <table class="w-full text-sm mb-6">

        <thead class="border-b">
            <tr>
                <th class="py-2">Service</th>
                <th>Price</th>
                <th>Staff Share</th>
                <th>Annex Share</th>
            </tr>
        </thead>

        <tbody>
        @foreach($bill->items as $item)
            <tr class="border-b">
                <td class="py-2">
                    {{ $item->service->name }}
                </td>
                <td>
                    ₦{{ number_format($item->price,2) }}
                </td>
                <td class="text-accent">
                    ₦{{ number_format($item->staff_share,2) }}
                </td>
                <td>
                    ₦{{ number_format($item->annex_share,2) }}
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>

    <div class="text-right space-y-2">
        <p><strong>Total:</strong> ₦{{ number_format($bill->total_amount,2) }}</p>
        <p class="text-green-600"><strong>Refunded:</strong> ₦{{ number_format($bill->refunds->sum('amount'),2) }}</p>
        <p class="text-accent">
            <strong>Total Staff Share:</strong> ₦{{ number_format($bill->total_staff_share,2) }}
        </p>
        <p>
            <strong>Total Annex Share:</strong> ₦{{ number_format($bill->total_annex_share,2) }}
        </p>
    </div>

    <div class="bg-white p-8 rounded-2xl shadow mt-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-primary">Refund History</h3>
            <a href="{{ route('refunds.create', ['bill_no' => $bill->bill_no]) }}" class="text-sm text-red-600 hover:underline">Add another refund</a>
        </div>

        @if($bill->refunds->isEmpty())
            <p class="text-gray-500 text-sm">No refunds recorded for this bill yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr>
                            <th class="py-3 text-left">Reason</th>
                            <th class="py-3 text-left">Amount</th>
                            <th class="py-3 text-left">Date</th>
                            <th class="py-3 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bill->refunds as $refund)
                            <tr class="border-b">
                                <td class="py-3">{{ $refund->reason }}</td>
                                <td class="py-3 text-red-600">₦{{ number_format($refund->amount,2) }}</td>
                                <td class="py-3">{{ $refund->created_at->format('d M Y h:i A') }}</td>
                                <td class="py-3">
                                    <a href="{{ route('refunds.edit', $refund) }}" class="text-sm text-primary hover:underline">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

@endsection