@extends('layouts.app')

@section('page-title', 'Radiographer Worklist')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow">
        <h2 class="text-2xl font-bold text-primary">Radiographer Worklist</h2>
        <p class="text-sm text-gray-600 mt-1">Only services marked as X-Ray appear here. Upload images for the assigned radiologist, then print the completed report.</p>
    </div>

    @include('components.table-filters', [
        'routeName' => 'staff.results.index',
        'searchPlaceholder' => 'Search patient, bill, or investigation...',
        'status' => $status,
        'statusLabel' => 'Workflow status',
        'statusOptions' => [
            '' => 'All statuses',
            'awaiting_upload' => 'Awaiting imaging upload',
            'awaiting_report' => 'Awaiting radiologist report',
            'report_ready' => 'Report ready to print',
        ],
    ])

    <div class="bg-white rounded-2xl shadow overflow-x-auto">
        <table class="w-full min-w-[850px] text-sm">
            <thead class="border-b bg-lightbg">
                <tr>
                    <th class="py-3 px-4 text-left">Patient / Bill</th>
                    <th class="py-3 px-4 text-left">Investigation</th>
                    <th class="py-3 px-4 text-left">Workflow status</th>
                    <th class="py-3 px-4 text-left">Radiologist</th>
                    <th class="py-3 px-4 text-left">Updated</th>
                    <th class="py-3 px-4 text-right">Next action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($studies as $item)
                    @php
                        $result = $item->result;
                        $statusLabel = ! $result
                            ? 'Awaiting imaging upload'
                            : ($result->reported_at ? 'Report ready to print' : 'Awaiting radiologist report');
                        $statusClass = ! $result
                            ? 'bg-amber-100 text-amber-800'
                            : ($result->reported_at ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800');
                    @endphp
                    <tr class="border-b align-top">
                        <td class="py-3 px-4">
                            <div class="font-medium">{{ $item->bill->patient_name ?? 'Walk-in' }}</div>
                            <div class="text-gray-500">{{ $item->bill->bill_no }}</div>
                        </td>
                        <td class="py-3 px-4">{{ $item->service->name }}</td>
                        <td class="py-3 px-4">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                            @if($result)
                                <div class="mt-1 text-xs text-gray-500">{{ $result->images->count() }} file(s)</div>
                            @endif
                        </td>
                        <td class="py-3 px-4">{{ $result?->assignedRadiologist?->name ?? '—' }}</td>
                        <td class="py-3 px-4">{{ optional($result?->updated_at ?? $item->bill->created_at)->format('d M Y h:i A') }}</td>
                        <td class="py-3 px-4 text-right">
                            @if($result?->reported_at)
                                <a href="{{ route('staff.results.print', $result) }}" class="inline-flex rounded-lg bg-primary px-4 py-2 font-medium text-white hover:bg-secondary">Print report</a>
                            @else
                                <a href="{{ route('staff.results.entry', $item->bill) }}" class="inline-flex rounded-lg bg-accent px-4 py-2 font-medium text-white hover:bg-green-600">
                                    {{ $result ? 'View imaging status' : 'Start imaging' }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">No paid investigations match these filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">@include('components.table-pagination', ['paginator' => $studies])</div>
    </div>
</div>
@endsection
