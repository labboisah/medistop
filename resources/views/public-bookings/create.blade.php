@extends('layouts.guest')

@section('title', 'Book an Investigation')

@section('content')
<div>
    <a href="{{ url('/') }}" class="text-sm font-semibold text-primary hover:underline">← Medistop Diagnostic Annex</a>
    <h1 class="mt-6 text-3xl font-bold text-primary">Book an Investigation</h1>
    <p class="mt-2 text-sm text-gray-600">Choose your investigations and provide your contact details. The center will confirm your request; payment is made at the center.</p>

    @if($errors->any())
        <div class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-700">
            Please review the highlighted details and select at least one investigation.
        </div>
    @endif

    <form method="POST" action="{{ route('public.investigation-bookings.store') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="patient_name" class="mb-1 block text-sm font-medium">Patient name</label>
            <input id="patient_name" name="patient_name" value="{{ old('patient_name') }}" required maxlength="255" autocomplete="name" class="w-full rounded-lg border-gray-300 px-4 py-3 focus:border-accent focus:ring-accent">
            @error('patient_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="mb-1 block text-sm font-medium">Phone number</label>
            <input id="phone" name="phone" value="{{ old('phone') }}" required maxlength="30" type="tel" autocomplete="tel" class="w-full rounded-lg border-gray-300 px-4 py-3 focus:border-accent focus:ring-accent">
            @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="gender" class="mb-1 block text-sm font-medium">Gender (optional)</label>
                <select id="gender" name="gender" class="w-full rounded-lg border-gray-300 px-3 py-3 focus:border-accent focus:ring-accent">
                    <option value="">Select</option>
                    <option value="male" @selected(old('gender') === 'male')>Male</option>
                    <option value="female" @selected(old('gender') === 'female')>Female</option>
                </select>
            </div>
            <div>
                <label for="age_years" class="mb-1 block text-sm font-medium">Age (years)</label>
                <input id="age_years" name="age_years" type="number" min="0" max="150" value="{{ old('age_years') }}" class="w-full rounded-lg border-gray-300 px-4 py-3 focus:border-accent focus:ring-accent">
            </div>
        </div>
        <div>
            <label for="age_months" class="mb-1 block text-sm font-medium">Additional months (optional)</label>
            <input id="age_months" name="age_months" type="number" min="0" max="11" value="{{ old('age_months') }}" class="w-full rounded-lg border-gray-300 px-4 py-3 focus:border-accent focus:ring-accent">
        </div>

        <fieldset>
            <legend class="mb-2 text-sm font-semibold">Select investigations</legend>
            <div class="max-h-72 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3">
                @forelse($services as $service)
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-md p-2 hover:bg-gray-50">
                        <span class="flex items-center gap-3">
                            <input type="checkbox" name="services[]" value="{{ $service->id }}" @checked(in_array((string) $service->id, old('services', []), true)) class="rounded border-gray-300 text-accent focus:ring-accent">
                            <span class="text-sm">{{ $service->name }}</span>
                        </span>
                        <span class="whitespace-nowrap text-sm font-semibold">NGN {{ number_format($service->price, 2) }}</span>
                    </label>
                @empty
                    <p class="p-2 text-sm text-gray-500">No investigations are currently available for booking.</p>
                @endforelse
            </div>
            @error('services')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('services.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </fieldset>

        <label class="flex items-start gap-2 text-xs text-gray-600">
            <input type="checkbox" required class="mt-0.5 rounded border-gray-300 text-accent focus:ring-accent">
            <span>I understand this is a booking request. I will visit the center to confirm the request and pay before the investigation is performed.</span>
        </label>

        <button type="submit" @disabled($services->isEmpty()) class="w-full rounded-lg bg-accent px-5 py-3 font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">Submit Booking Request</button>
    </form>
</div>
@endsection
