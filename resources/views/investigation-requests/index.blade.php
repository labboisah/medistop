@extends('layouts.app')

@section('title', 'Investigation Requests')
@section('page-title', 'Investigation Requests')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-primary dark:text-white">Investigation Requests</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">View payment, imaging, and report progress for each requested investigation.</p>
        </div>
        <a href="{{ route('investigation-requests.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-accent px-4 py-2.5 font-semibold text-white shadow hover:bg-green-600">
            <span aria-hidden="true" class="text-lg leading-none">+</span>
            New Investigation
        </a>
    </div>

    <section class="overflow-hidden rounded-xl bg-white shadow dark:bg-secondary">
        <form method="GET" action="{{ route('investigation-requests.index') }}" class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row dark:border-gray-700">
            <label for="investigation-search" class="sr-only">Search investigations</label>
            <input id="investigation-search" type="search" name="q" value="{{ $search }}" placeholder="Search by patient, bill number, or investigation..." class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
            <button type="submit" class="rounded-lg bg-primary px-5 py-2 font-semibold text-white hover:bg-secondary">Search</button>
            @if($search !== '')
                <a href="{{ route('investigation-requests.index') }}" class="rounded-lg border border-gray-300 px-5 py-2 text-center font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-white dark:hover:bg-gray-700">Clear</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-600 dark:bg-primary dark:text-gray-300">
                    <tr>
                        <th class="px-5 py-3">Patient / Bill</th>
                        <th class="px-5 py-3">Investigation</th>
                        <th class="px-5 py-3">Payment</th>
                        <th class="px-5 py-3">Imaging / Report status</th>
                        <th class="px-5 py-3">Requested</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($items as $item)
                        @php
                            $bill = $item->bill;
                            $result = $item->result;
                            $imagingStatus = 'Awaiting imaging';
                            $statusClasses = 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200';

                            if ($bill->payment_status !== 'paid') {
                                $imagingStatus = 'Awaiting payment';
                                $statusClasses = 'bg-amber-100 text-amber-800';
                            } elseif ($result && $result->reported_at) {
                                $imagingStatus = 'Result submitted';
                                $statusClasses = 'bg-green-100 text-green-800';
                            } elseif ($result && $result->images->isNotEmpty()) {
                                $imagingStatus = 'Image uploaded';
                                $statusClasses = 'bg-blue-100 text-blue-800';
                            } elseif ($result && $result->performed_at) {
                                $imagingStatus = 'Imaging in progress';
                                $statusClasses = 'bg-indigo-100 text-indigo-800';
                            }

                            $paymentLabel = ucfirst(str_replace('_', ' ', $bill->payment_status ?? 'unpaid'));
                        @endphp
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="font-semibold text-primary dark:text-white">{{ $bill->patient_name ?: 'Unnamed patient' }}</div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $bill->bill_no }}</div>
                            </td>
                            <td class="px-5 py-4 font-medium">{{ $item->service->name ?? 'Investigation' }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $bill->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ $paymentLabel }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $imagingStatus }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">{{ $bill->created_at?->format('d M Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <a href="{{ route('investigation-requests.show', $bill) }}" class="font-semibold text-accent hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-gray-500 dark:text-gray-300">
                                <p class="font-semibold">{{ $search !== '' ? 'No matching investigations found.' : 'No investigation requests yet.' }}</p>
                                @if($search === '')
                                    <p class="mt-1 text-sm">Select “New Investigation” to register the first request.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="border-t border-gray-200 p-4 dark:border-gray-700">{{ $items->links() }}</div>
        @endif
    </section>
</div>
@endsection
