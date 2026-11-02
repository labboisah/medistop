@extends('layouts.app')

@section('page-title', 'Imaging Worklist')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white p-6 rounded-2xl shadow">
        <h2 class="text-2xl font-bold text-primary">Imaging Worklist</h2>
        <p class="text-sm text-gray-600 mt-1">X-Ray studies sent to you by radiographers and awaiting your report.</p>
    </div>

    <div class="bg-white rounded-2xl shadow overflow-x-auto">
        @include('components.table-filters', ['routeName' => 'staff.results.index', 'searchPlaceholder' => 'Search patient, bill, investigation, or radiographer...'])
        <table class="w-full text-sm">
            <thead class="border-b bg-lightbg">
                <tr>
                    <th class="py-3 px-4 text-left">Patient / Bill</th>
                    <th class="py-3 px-4 text-left">Investigation</th>
                    <th class="py-3 px-4 text-left">Sent by</th>
                    <th class="py-3 px-4 text-left">Imaging files</th>
                    <th class="py-3 px-4 text-left">Date</th>
                    <th class="py-3 px-4 text-left">Reporting time left</th>
                    <th class="py-3 px-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($studies as $study)
                    <tr class="border-b">
                        <td class="py-3 px-4">
                            <div class="font-medium">{{ $study->bill->patient_name ?? 'Walk-in' }}</div>
                            <div class="text-gray-500">{{ $study->bill->bill_no }}</div>
                        </td>
                        <td class="py-3 px-4">{{ $study->billItem->service->name }}</td>
                        <td class="py-3 px-4">{{ optional($study->performer)->name ?? 'Radiographer' }}</td>
                        <td class="py-3 px-4">
                            <div class="space-y-2">
                                @forelse($study->images as $image)
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="max-w-40 truncate" title="{{ $image->original_name }}">{{ $image->original_name }}</span>
                                        <a href="{{ route('staff.results.images.view', $image) }}" target="_blank" rel="noopener" class="text-primary hover:underline">View</a>
                                        <a href="{{ route('staff.results.images.download', $image) }}" class="text-primary hover:underline">Download</a>
                                    </div>
                                @empty
                                    <span class="text-gray-500">No files attached</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="py-3 px-4">{{ optional($study->performed_at)->format('d M Y h:i A') ?? '—' }}</td>
                        @php($reportingDeadline = ($study->performed_at ?? $study->created_at)?->copy()->addHours(24))
                        <td class="py-3 px-4">
                            @if($reportingDeadline)
                                <span data-report-countdown="{{ $reportingDeadline->timestamp * 1000 }}" class="font-semibold text-amber-800">Calculating…</span>
                            @else
                                <span class="text-gray-500">Unavailable</span>
                            @endif
                            @if($study->status === 'draft')
                                <div class="mt-1 text-xs font-medium text-blue-700">Draft saved</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('staff.results.entry', $study->bill) }}" class="inline-block rounded-lg bg-accent px-4 py-2 text-white">{{ $study->status === 'draft' ? 'Continue draft' : 'Review and report' }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">No studies are awaiting your report.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">@include('components.table-pagination', ['paginator' => $studies])</div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const updateCountdowns = () => {
        document.querySelectorAll('[data-report-countdown]').forEach((clock) => {
            let seconds = Math.floor((Number(clock.dataset.reportCountdown) - Date.now()) / 1000);
            const overdue = seconds < 0;
            seconds = Math.abs(seconds);
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainingSeconds = seconds % 60;
            const time = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
            clock.textContent = overdue ? `Overdue by ${time}` : `${time} remaining`;
            clock.classList.toggle('text-red-700', overdue);
            clock.classList.toggle('text-amber-800', !overdue);
        });
    };

    updateCountdowns();
    window.setInterval(updateCountdowns, 1000);
});
</script>
@endsection
