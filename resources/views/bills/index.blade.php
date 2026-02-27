@extends('layouts.app')

@section('page-title', 'Bills')

@section('content')

<div class="mb-6 flex justify-between">
    <h2 class="text-xl font-bold text-primary">All Bills</h2>

    <a href="{{ route('bills.create') }}"
       class="bg-accent text-white px-5 py-2 rounded-lg shadow">
        + New Bill
    </a>
</div>

<div class="bg-white rounded-2xl shadow p-6 overflow-x-auto">

    <table class="w-full text-left text-sm">

        <thead class="border-b">
            <tr>
                <th class="py-3">Bill No</th>
                <th>Patient</th>
                <th>Total (₦)</th>
                <th>Staff Share</th>
                <th>Date</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>

        <tbody>
        @foreach($bills as $bill)
            <tr class="border-b hover:bg-lightbg">
                <td class="py-3 font-semibold text-primary">
                    {{ $bill->bill_no }}
                </td>

                <td>
                    {{ $bill->patient_name ?? 'Walk-in' }}
                </td>

                <td>
                    ₦{{ number_format($bill->total_amount,2) }}
                </td>

                <td class="text-accent font-semibold">
                    ₦{{ number_format($bill->total_staff_share,2) }}
                </td>

                <td>
                    {{ $bill->created_at->format('d M Y') }}
                </td>

                <td class="text-right">
                    <a href="{{ route('bills.show', $bill) }}"
                       class="text-blue-600 hover:underline">
                        View
                    </a>
                    @if($bill->balance > 0)
                        <a href="{{ route('payments.create', $bill) }}"
                        class="bg-accent text-white px-6 py-3 rounded-xl">
                            Record Payment
                        </a>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>

    <div class="mt-6">
        {{ $bills->links() }}
    </div>

</div>

@endsection