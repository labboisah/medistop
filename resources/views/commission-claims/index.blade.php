@extends('layouts.app')

@section('page-title', 'Commission Claims')

@section('content')
<div class="bg-white p-6 rounded-2xl shadow overflow-x-auto">
    <h1 class="text-2xl font-bold text-primary mb-5">Staff commission claims</h1>
    @if(session('success'))<p class="mb-4 text-sm text-green-700">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
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
                        <form method="POST" action="{{ route('commission-claims.settle', $claim) }}" class="space-y-2">
                            @csrf
                            <label class="block text-xs text-gray-600">Actual amount paid</label>
                            <input type="number" name="settled_amount" min="0" max="{{ $claim->requested_amount }}" step="0.01" value="{{ $claim->requested_amount }}" required class="w-44 rounded border-gray-300">
                            <textarea name="settlement_note" rows="1" placeholder="Settlement note (optional)" class="w-full rounded border-gray-300"></textarea>
                            <button class="rounded bg-green-700 px-3 py-2 font-semibold text-white">Settle</button>
                        </form>
                        <form method="POST" action="{{ route('commission-claims.reject', $claim) }}" class="mt-2">@csrf<button class="text-red-700 underline">Reject claim</button></form>
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
    <div class="mt-5">@include('components.table-pagination', ['paginator' => $claims])</div>
</div>
@endsection
