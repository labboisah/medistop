@extends('layouts.app')

@section('page-title', 'Review Booking')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <section class="rounded-2xl bg-white p-6 shadow">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><p class="text-sm text-gray-500">Public booking reference</p><h2 class="text-2xl font-bold text-primary">{{ $booking->reference }}</h2></div>
            <span class="rounded-full px-4 py-2 text-xs font-semibold {{ $booking->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-700' }}">{{ strtoupper($booking->status) }}</span>
        </div>
        <div class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
            <p><strong>Patient:</strong> {{ $booking->patient_name }}</p>
            <p><strong>Phone:</strong> <a class="text-blue-700 underline" href="tel:{{ $booking->phone }}">{{ $booking->phone }}</a></p>
            <p><strong>Gender:</strong> {{ ucfirst($booking->gender ?? 'Not specified') }}</p>
            <p><strong>Age:</strong> {{ $booking->age ?? 'Not specified' }}</p>
            <p><strong>Submitted:</strong> {{ $booking->created_at->format('d M Y, h:i A') }}</p>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl bg-white shadow">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-gray-50"><tr><th class="px-5 py-3">Investigation</th><th class="px-5 py-3 text-right">Quoted amount</th></tr></thead>
                <tbody>
                    @foreach($booking->items as $item)
                        <tr class="border-b"><td class="px-5 py-3">{{ $item->service->name }}</td><td class="px-5 py-3 text-right">NGN {{ number_format($item->price, 2) }}</td></tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50"><tr><th class="px-5 py-3 text-right">Total</th><th class="px-5 py-3 text-right">NGN {{ number_format($booking->items->sum('price'), 2) }}</th></tr></tfoot>
            </table>
        </div>

        <div class="flex flex-wrap gap-3 p-5">
            @if($booking->status === 'pending')
                <form method="POST" action="{{ route('investigation-bookings.accept', $booking) }}" onsubmit="return confirm('Accept this booking and create a bill using the quoted investigation prices?')">
                    @csrf
                    <button type="submit" class="rounded-lg bg-accent px-5 py-3 font-semibold text-white hover:bg-green-700">Accept Booking &amp; Take Payment</button>
                </form>
            @elseif($booking->bill)
                @if($booking->bill->balance > 0)
                    <a href="{{ route('payments.create', $booking->bill) }}" class="rounded-lg bg-accent px-5 py-3 font-semibold text-white">Accept Payment</a>
                @else
                    <a href="{{ route('payments.receipt', ['bill' => $booking->bill, 'type' => 'a4']) }}" class="rounded-lg bg-primary px-5 py-3 font-semibold text-white">Print Receipt</a>
                @endif
            @endif
            <a href="{{ route('investigation-bookings.index') }}" class="rounded-lg border border-gray-300 px-5 py-3 font-semibold text-gray-700">Back to bookings</a>
        </div>
    </section>
</div>
@endsection
