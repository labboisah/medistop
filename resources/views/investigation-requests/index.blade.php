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
        <a href="{{ route('investigation-requests.create') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-lg bg-accent px-5 py-2 text-white shadow transition hover:bg-green-600 sm:self-auto">
            <span aria-hidden="true">+</span> New Investigation
        </a>
    </div>

    <section class="overflow-hidden rounded-xl bg-white shadow dark:bg-secondary">
        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
            <div class="flex flex-col gap-3 md:flex-row md:items-end">
                <div class="relative min-w-0 flex-1">
                    <label for="investigation-search" class="sr-only">Search investigations</label>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" stroke-width="2" />
                        <path stroke-linecap="round" stroke-width="2" d="m20 20-4-4" />
                    </svg>
                    <input id="investigation-search" type="search" placeholder="Search by patient, bill number, or investigation..." class="w-full rounded-lg border border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
                </div>
                <div class="md:w-44 md:shrink-0">
                    <label for="investigation-date-from" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">From date</label>
                    <input id="investigation-date-from" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
                </div>
                <div class="md:w-44 md:shrink-0">
                    <label for="investigation-date-to" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">To date</label>
                    <input id="investigation-date-to" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
                </div>
            </div>
        </div>

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
                            <td colspan="6" data-empty-state class="px-5 py-12 text-center text-gray-500 dark:text-gray-300">
                                <p class="font-semibold">No investigation requests yet.</p>
                                <p class="mt-1 text-sm">Select “New Investigation” to register the first request.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('investigation-search');
        const dateFromInput = document.getElementById('investigation-date-from');
        const dateToInput = document.getElementById('investigation-date-to');
        const rows = Array.from(document.querySelectorAll('[data-investigation-row]'));
        const emptyState = document.querySelector('[data-empty-state]');

        if (!searchInput) return;

        const filterRows = () => {
            const query = searchInput.value.trim().toLocaleLowerCase();
            const dateFrom = dateFromInput?.value ?? '';
            const dateTo = dateToInput?.value ?? '';
            const hasFilters = query.length > 0 || dateFrom !== '' || dateTo !== '';
            let visibleCount = 0;

            rows.forEach((row) => {
                const date = row.dataset.date ?? '';
                const matches = row.textContent.toLocaleLowerCase().includes(query)
                    && (!dateFrom || date >= dateFrom)
                    && (!dateTo || date <= dateTo);
                row.hidden = !matches;
                if (matches) visibleCount++;
            });

            if (emptyState) {
                emptyState.hidden = rows.length > 0;
            } else {
                const existingSearchEmpty = document.querySelector('[data-search-empty]');
                if (visibleCount === 0 && hasFilters && !existingSearchEmpty) {
                    const tbody = document.querySelector('tbody');
                    const row = document.createElement('tr');
                    row.dataset.searchEmpty = '';
                    row.innerHTML = '<td colspan="6" class="px-5 py-12 text-center text-gray-500 dark:text-gray-300"><p class="font-semibold">No matching investigations found.</p></td>';
                    tbody?.appendChild(row);
                } else if (visibleCount > 0 || !hasFilters) {
                    existingSearchEmpty?.remove();
                }
            }
        };

        searchInput.addEventListener('input', filterRows);
        dateFromInput?.addEventListener('input', filterRows);
        dateToInput?.addEventListener('input', filterRows);
    });
</script>
@endsection
