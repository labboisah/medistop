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
