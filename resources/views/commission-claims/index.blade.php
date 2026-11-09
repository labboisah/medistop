@extends('layouts.app')

@section('page-title', 'Commission Claims')

@section('content')
<div class="bg-white p-6 rounded-2xl shadow">
    <h1 class="text-2xl font-bold text-primary mb-5">Staff commission claims</h1>
    @if(session('success'))<p class="mb-4 text-sm text-green-700">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    @php($pendingClaims = $claims->getCollection()->where('status', 'pending'))
    <section class="mb-7 space-y-4">
        <h2 class="text-lg font-bold text-primary">Pending claims to settle ({{ $pendingClaims->count() }})</h2>
        @forelse($pendingClaims as $claim)
            <article class="grid gap-5 rounded-xl border border-amber-200 bg-amber-50 p-5 lg:grid-cols-2 lg:items-center">
                <div>
                    <p class="font-bold text-gray-900">{{ $claim->staff->name }} <span class="font-normal text-gray-600">({{ ucfirst($claim->staff->staff_type ?? 'staff') }})</span></p>
                    <p class="mt-1 text-sm text-gray-700">Requested <strong>NGN {{ number_format($claim->requested_amount, 2) }}</strong> · {{ $claim->created_at->format('d M Y h:i A') }}</p>
                    @if($claim->staff_note)<p class="mt-1 text-sm text-gray-600">{{ $claim->staff_note }}</p>@endif
                </div>
                <div class="text-sm text-gray-700">
                    <p class="font-semibold">Payment account</p>
                    <p>{{ $claim->staff->bank_name ?: 'Bank not added' }}</p>
                    <p>{{ $claim->staff->bank_account_name }} · {{ $claim->staff->bank_account_number }}</p>
                </div>
                <form id="settle-claim-{{ $claim->id }}" method="POST" action="{{ route('commission-claims.settle', $claim) }}" class="grid w-full gap-3 md:grid-cols-2 lg:col-span-2">
                    @csrf
                    <div>
                        <label for="settled_amount_{{ $claim->id }}" class="block text-xs font-semibold text-gray-700">Actual amount paid</label>
                        <input id="settled_amount_{{ $claim->id }}" type="number" name="settled_amount" min="0.01" max="{{ $claim->requested_amount }}" step="0.01" value="{{ $claim->requested_amount }}" required class="mt-1 w-full rounded-lg border-gray-300">
                    </div>
                    <div>
                        <label for="settlement_note_{{ $claim->id }}" class="block text-xs font-semibold text-gray-700">Note (optional)</label>
                        <input id="settlement_note_{{ $claim->id }}" type="text" name="settlement_note" class="mt-1 w-full rounded-lg border-gray-300">
                    </div>
                </form>
                <input type="submit" form="settle-claim-{{ $claim->id }}" value="Settle Claim" style="display:block;width:100%;min-height:48px;padding:12px 20px;border:0;border-radius:8px;background:#15803d;color:#fff;font-size:16px;font-weight:700;text-align:center;cursor:pointer" aria-label="Submit settlement for {{ $claim->staff->name }}'s claim">
                <form method="POST" action="{{ route('commission-claims.reject', $claim) }}" class="lg:col-span-2">
                    @csrf
                    <button type="submit" class="font-semibold text-red-700 underline">Reject claim</button>
                </form>
            </article>
        @empty
            <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">There are no pending claims assigned to you.</p>
        @endforelse
    </section>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="border-b bg-lightbg"><tr>
            <th class="p-3 text-left">Staff</th><th class="p-3 text-left">Payment account</th><th class="p-3 text-right">Requested</th><th class="p-3 text-left">Date / note</th><th class="p-3 text-left">Settlement</th>
        </tr></thead>
        <tbody>
        @forelse($claims as $claim)
            <tr class="border-b align-top">
                <td class="p-3">{{ $claim->staff->name }}<br><span class="text-gray-500">{{ ucfirst($claim->staff->staff_type ?? 'staff') }}</span></td>
                <td class="p-3">{{ $claim->staff->bank_name ?: 'Bank not added' }}<br>{{ $claim->staff->bank_account_name }}<br>{{ $claim->staff->bank_account_number }}</td>
                <td class="p-3 text-right">NGN {{ number_format($claim->requested_amount, 2) }}</td>
                <td class="p-3">{{ $claim->created_at->format('d M Y h:i A') }}<br>{{ $claim->staff_note }}</td>
                <td class="p-3">
                    @if($claim->status === 'pending')
                        <span class="font-semibold text-amber-800">Pending · action above</span>
                    @else
                        <span class="font-semibold capitalize">{{ $claim->status }}</span>
                        @if($claim->settled_amount !== null)<br>Paid: NGN {{ number_format($claim->settled_amount, 2) }}@endif
                        @if($claim->settlement_note)<br>{{ $claim->settlement_note }}@endif
                    @endif
                </td>
            </tr>
        @empty<tr><td colspan="5" class="p-6 text-center text-gray-500">No claims have been submitted to your account.</td></tr>@endforelse
        </tbody>
    </table>
    </div>
    <div class="mt-5">@include('components.table-pagination', ['paginator' => $claims])</div>
</div>
@endsection
