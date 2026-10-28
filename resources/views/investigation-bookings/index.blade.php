@extends('layouts.app')

@section('page-title', 'Public Booking Requests')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold text-primary">Public Investigation Bookings</h2>
        <p class="mt-1 text-sm text-gray-500">Review requests submitted from the public booking page.</p>
    </div>
    <a href="{{ route('investigation-requests.create') }}" class="rounded-lg bg-accent px-4 py-2 font-semibold text-white">Register at center</a>
</div>

<form method="GET" action="{{ route('investigation-bookings.index') }}" class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl bg-white p-5 shadow">
    <div>
        <label for="from" class="mb-1 block text-sm font-medium text-gray-700">From</label>
        <input id="from" name="from" type="date" value="{{ $from }}" class="rounded-lg border-gray-300 px-3 py-2 focus:border-accent focus:ring-accent">
    </div>
    <div>
        <label for="to" class="mb-1 block text-sm font-medium text-gray-700">To</label>
        <input id="to" name="to" type="date" value="{{ $to }}" min="{{ $from }}" class="rounded-lg border-gray-300 px-3 py-2 focus:border-accent focus:ring-accent">
    </div>
    <button type="submit" class="rounded-lg bg-primary px-5 py-2 font-semibold text-white hover:bg-secondary">Filter</button>
    @if($from || $to)
        <a href="{{ route('investigation-bookings.index') }}" class="px-3 py-2 text-sm font-semibold text-gray-600 hover:text-primary">Clear dates</a>
    @endif
</form>

<div class="mb-5 grid gap-4 sm:grid-cols-3">
    <div class="rounded-2xl bg-white p-5 shadow">
        <p class="text-sm text-gray-500">Total bookings</p>
        <p class="mt-1 text-3xl font-bold text-primary">{{ number_format($stats['total']) }}</p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow">
        <p class="text-sm text-gray-500">Pending</p>
        <p class="mt-1 text-3xl font-bold text-amber-600">{{ number_format($stats['pending']) }}</p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow">
        <p class="text-sm text-gray-500">Accepted</p>
        <p class="mt-1 text-3xl font-bold text-green-700">{{ number_format($stats['accepted']) }}</p>
    </div>
</div>

<p class="mb-3 text-sm text-gray-500">
    Statistics
    @if($from && $to)
        from {{ \Carbon\Carbon::parse($from)->format('d M Y') }} through {{ \Carbon\Carbon::parse($to)->format('d M Y') }}.
    @elseif($from)
        from {{ \Carbon\Carbon::parse($from)->format('d M Y') }} onward.
    @elseif($to)
        through {{ \Carbon\Carbon::parse($to)->format('d M Y') }}.
    @else
        across all dates.
    @endif
</p>

<div class="overflow-x-auto rounded-2xl bg-white p-5 shadow">
    <table class="w-full min-w-[700px] text-left text-sm">
        <thead class="border-b text-gray-500">
            <tr><th class="py-3">Reference</th><th>Patient</th><th>Phone</th><th>Investigations</th><th>Status</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
                <tr class="border-b last:border-0">
                    <td class="py-3 font-semibold text-primary">{{ $booking->reference }}</td>
                    <td>{{ $booking->patient_name }}</td>
                    <td>{{ $booking->phone }}</td>
                    <td>{{ $booking->items->pluck('service.name')->filter()->join(', ') }}</td>
                    <td><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $booking->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-700' }}">{{ strtoupper($booking->status) }}</span></td>
                    <td>{{ $booking->created_at->format('d M Y H:i') }}</td>
                    <td><a href="{{ route('investigation-bookings.show', $booking) }}" class="font-semibold text-blue-700 hover:underline">Review</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-8 text-center text-gray-500">No public booking requests have been received.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-5">{{ $bookings->links() }}</div>
</div>
@endsection
