@extends('layouts.app')

@section('title', 'Investigation Requests')
@section('page-title', 'Investigation Requests')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between gap-4" style="display:flex; flex-direction:row; align-items:center; justify-content:space-between; gap:1rem;">
        <div>
            <h2 class="text-2xl font-bold text-primary dark:text-white">Investigation Requests</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">View payment, imaging, and report progress for each requested investigation.</p>
        </div>
        <a href="{{ route('investigation-requests.create') }}" class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-accent px-5 py-2 text-white shadow transition hover:bg-green-600" style="white-space:nowrap;">
            <span aria-hidden="true">+</span> New Investigation
        </a>
    </div>

    <section class="overflow-hidden rounded-xl bg-white shadow dark:bg-secondary">
        <form id="investigation-filters" method="GET" action="{{ route('investigation-requests.index') }}" class="border-b border-gray-200 p-4 dark:border-gray-700">
            <div class="flex items-end gap-3 overflow-x-auto" style="display:flex; flex-direction:row; flex-wrap:nowrap; align-items:flex-end; gap:.75rem; overflow-x:auto;">
                <div class="relative min-w-0 flex-1" style="flex:1 1 auto; min-width:16rem;">
                    <label for="investigation-search" class="sr-only">Search investigations</label>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" stroke-width="2" />
                        <path stroke-linecap="round" stroke-width="2" d="m20 20-4-4" />
                    </svg>
                    <input id="investigation-search" name="q" type="search" value="{{ $search }}" placeholder="Search by patient, bill number, or investigation..." class="w-full rounded-lg border border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
                </div>
                <div class="shrink-0" style="flex:0 0 11rem;">
                    <label for="investigation-date-from" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">From date</label>
                    <input id="investigation-date-from" name="date_from" type="date" value="{{ $dateFrom }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
                </div>
                <div class="shrink-0" style="flex:0 0 11rem;">
                    <label for="investigation-date-to" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">To date</label>
                    <input id="investigation-date-to" name="date_to" type="date" value="{{ $dateTo }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
                </div>
            </div>
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
                        <tr data-investigation-row data-date="{{ $bill->created_at?->format('Y-m-d') }}" class="text-gray-700 dark:text-gray-200">
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
                                @if($search !== '' || $dateFrom !== '' || $dateTo !== '')
                                    <p class="font-semibold">No matching investigations found.</p>
                                @else
                                    <p class="font-semibold">No investigation requests yet.</p>
                                    <p class="mt-1 text-sm">Select “New Investigation” to register the first request.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="flex flex-col gap-3 border-t border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-300">Showing {{ $items->firstItem() }}–{{ $items->lastItem() }} of {{ $items->total() }} investigations</p>
                <div>{{ $items->links() }}</div>
            </div>
        @endif

    </section>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const filterForm = document.getElementById('investigation-filters');
        const searchInput = document.getElementById('investigation-search');
        const dateFromInput = document.getElementById('investigation-date-from');
        const dateToInput = document.getElementById('investigation-date-to');

        if (!filterForm || !searchInput) return;

        let searchTimer;
        searchInput.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => filterForm.requestSubmit(), 350);
        });
        dateFromInput?.addEventListener('change', () => filterForm.requestSubmit());
        dateToInput?.addEventListener('change', () => filterForm.requestSubmit());
    });
</script>
@endsection
