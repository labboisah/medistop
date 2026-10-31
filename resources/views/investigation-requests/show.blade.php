@extends('layouts.app')

@section('page-title', 'Investigation Request')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <section class="rounded-2xl bg-white p-6 shadow">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">Request reference</p>
                <h2 class="text-2xl font-bold text-primary">{{ $bill->bill_no }}</h2>
            </div>
            <span class="rounded-full px-4 py-2 text-xs font-semibold {{ $bill->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-800' }}">
                {{ strtoupper($bill->payment_status) }}
            </span>
        </div>

        @if(session('success'))
            <div class="mt-5 rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
            <p><strong>Patient:</strong> {{ $bill->patient_name }}</p>
            <p><strong>Registered by:</strong> {{ $bill->user->name }}</p>
            <p><strong>Gender:</strong> {{ ucfirst($bill->gender ?? 'Not specified') }}</p>
            <p><strong>Age:</strong> {{ $bill->age ?? 'Not specified' }}</p>
            <p><strong>Registered:</strong> {{ $bill->created_at->format('d M Y, h:i A') }}</p>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl bg-white shadow">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-gray-50">
                    <tr><th class="px-5 py-3">Investigation</th><th class="px-5 py-3 text-right">Amount</th><th class="px-5 py-3 text-right">Report</th></tr>
                </thead>
                <tbody>
                    @foreach($bill->items as $item)
                        <tr class="border-b">
                            <td class="px-5 py-3">{{ $item->service->name }}</td>
                            <td class="px-5 py-3 text-right">NGN {{ number_format($item->price, 2) }}</td>
                            <td class="px-5 py-3 text-right">
                                @if($item->service->is_xray && $item->result?->reported_at)
                                    <a href="{{ route('staff.results.print', $item->result) }}" class="rounded-lg bg-primary px-3 py-2 font-semibold text-white">View / Print X-Ray Report</a>
                                @else
                                    <span class="text-sm text-gray-500">{{ $item->service->is_xray ? 'Report pending' : '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr><th class="px-5 py-3 text-right">Balance due</th><th class="px-5 py-3 text-right">NGN {{ number_format($bill->balance, 2) }}</th></tr>
                </tfoot>
            </table>
        </div>

        <div class="flex flex-wrap items-center gap-3 p-5">
            @if($bill->balance > 0 && auth()->user()->role === 'user')
                <a href="{{ route('payments.create', $bill) }}" class="rounded-lg bg-accent px-5 py-3 font-semibold text-white hover:bg-green-700">Accept Payment</a>
            @elseif($bill->balance > 0)
                <p class="text-sm text-gray-600">This request is awaiting payment. Send the patient and reference to the center desk for payment approval.</p>
            @endif

            @if($bill->payments->isNotEmpty() && auth()->user()->role === 'user')
                <a href="{{ route('payments.receipt', ['bill' => $bill, 'type' => 'a4']) }}" class="rounded-lg bg-primary px-5 py-3 font-semibold text-white">Print Receipt</a>
            @endif
        </div>
    </section>
</div>
@endsection
