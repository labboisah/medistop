@extends('layouts.guest')

@section('title', 'Booking Request Received')

@section('content')
<div>
    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-2xl text-green-700" aria-hidden="true">✓</div>
    <h1 class="text-3xl font-bold text-primary">Request received</h1>
    <p class="mt-2 text-sm text-gray-600">Bring this booking reference to Medistop Diagnostic Annex. The center will confirm your investigations and collect payment before the examination.</p>

    <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Booking reference</p>
        <p class="mt-1 text-2xl font-bold tracking-wider text-primary">{{ $booking->reference }}</p>
        <p class="mt-4 text-sm"><strong>Patient:</strong> {{ $booking->patient_name }}</p>
        <p class="mt-1 text-sm"><strong>Phone:</strong> {{ $booking->phone }}</p>
        <p class="mt-1 text-sm"><strong>Status:</strong> {{ ucfirst($booking->status) }}</p>
    </div>

    <div class="mt-5">
        <h2 class="font-semibold text-primary">Requested investigations</h2>
        <ul class="mt-2 space-y-2 text-sm text-gray-700">
            @foreach($booking->items as $item)
                <li class="flex justify-between gap-3"><span>{{ $item->service->name }}</span><span class="whitespace-nowrap">NGN {{ number_format($item->price, 2) }}</span></li>
            @endforeach
        </ul>
    </div>

    <a href="{{ url('/') }}" class="mt-7 inline-flex rounded-lg bg-primary px-5 py-3 font-semibold text-white hover:bg-secondary">Return to home</a>
</div>
@endsection
