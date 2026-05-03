@extends('layouts.app')

@section('page-title', 'Refunds')

@section('content')

<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-primary">Refunds</h2>
            <p class="text-sm text-gray-500">All refunds recorded by you.</p>
        </div>
        <a href="{{ route('refunds.create') }}"
           class="bg-accent text-white px-4 py-2 rounded-xl shadow hover:bg-green-600 transition">
            Add Refund
        </a>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-lightbg">
                <tr>
                    <th class="py-3 px-4">Bill</th>
                    <th class="py-3 px-4">Amount</th>
                    <th class="py-3 px-4">Reason</th>
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($refunds as $refund)
                    <tr class="border-t">
                        <td class="py-3 px-4">
                            <a href="{{ route('bills.show', $refund->bill) }}" class="text-primary hover:underline">
                                {{ $refund->bill->bill_no }}
                            </a>
                        </td>
                        <td class="py-3 px-4 font-semibold text-red-600">
                            ₦{{ number_format($refund->amount, 2) }}
                        </td>
                        <td class="py-3 px-4">{{ $refund->reason }}</td>
                        <td class="py-3 px-4">{{ $refund->created_at->format('d M Y h:i A') }}</td>
                        <td class="py-3 px-4 space-x-2">
                            <a href="{{ route('refunds.edit', $refund) }}"
                               class="px-3 py-1 bg-blue-600 text-white rounded-lg text-xs">Edit</a>
                            <form action="{{ route('refunds.destroy', $refund) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-3 py-1 bg-red-600 text-white rounded-lg text-xs"
                                    onclick="return confirm('Delete this refund?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="py-6 px-4 text-center text-gray-500" colspan="5">
                            No refunds recorded yet. Click "Add Refund" above to record a refund by bill number.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $refunds->links() }}
    </div>
</div>

@endsection
