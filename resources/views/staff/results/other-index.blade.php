@extends('layouts.app')

@section('page-title', 'Other Result Reporting')

@section('content')
<div class="mx-auto max-w-3xl rounded-2xl bg-white p-8 shadow">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-primary">Other Result Reporting</h2>
        <p class="mt-2 text-sm text-gray-600">Search by bill number to enter, update, or print results for non-X-Ray investigations. This screen is available to all staff.</p>
    </div>

    @if($errors->has('bill_no'))
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $errors->first('bill_no') }}</div>
    @endif

    <form method="POST" action="{{ route('staff.results.other.lookup') }}" class="space-y-5">
        @csrf
        <div>
            <label for="other-result-bill-number" class="mb-2 block text-sm font-medium">Bill number</label>
            <input id="other-result-bill-number" type="text" name="bill_no" value="{{ old('bill_no') }}" required autofocus
                   placeholder="Example: B260719-1234"
                   class="w-full rounded-xl border-gray-300 px-4 py-3 focus:border-accent focus:ring-accent">
        </div>
        <button type="submit" class="rounded-xl bg-accent px-6 py-3 font-semibold text-white hover:bg-green-600">Search non-X-Ray investigations</button>
    </form>
</div>
@endsection
