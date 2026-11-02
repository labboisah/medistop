@extends('layouts.app')

@section('page-title', 'Radiographer Worklist')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
    @endif

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
                    <th class="py-3 px-4 text-left">Uploaded imaging</th>
                    <th class="py-3 px-4 text-left">Radiologist</th>
                    <th class="py-3 px-4 text-left">Updated</th>
                    <th class="py-3 px-4 text-right">Next action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($studies as $item)
                    @php
                        $result = $item->result;
                        $hasImages = $result && $result->images->isNotEmpty();
                        $statusLabel = ! $hasImages
                            ? 'Awaiting imaging upload'
                            : ($result->reported_at ? 'Report ready to print' : 'X-ray uploaded · Awaiting radiologist report');
                        $statusClass = ! $hasImages
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
                                <div class="mt-1 text-xs text-gray-500">{{ $result->images->count() }} file(s) uploaded{{ $result->performed_at ? ' · ' . $result->performed_at->format('d M Y h:i A') : '' }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($result?->images->isNotEmpty())
                                <div class="grid min-w-[13rem] grid-cols-2 gap-2">
                                    @foreach($result->images as $image)
                                        <div class="min-w-0 rounded-lg border bg-white p-2">
                                            @if(str_starts_with(strtolower($image->mime_type ?? ''), 'image/'))
                                                <a href="{{ route('staff.results.images.view', $image) }}" target="_blank" rel="noopener">
                                                    <img src="{{ route('staff.results.images.view', $image) }}" alt="{{ $image->original_name }}" loading="lazy" class="h-24 w-full rounded object-contain">
                                                </a>
                                            @elseif(strtolower($image->mime_type ?? '') === 'application/pdf' || strtolower(pathinfo($image->original_name, PATHINFO_EXTENSION)) === 'pdf')
                                                <iframe src="{{ route('staff.results.images.view', $image) }}#page=1&view=Fit&toolbar=0&navpanes=0" title="PDF content preview: {{ $image->original_name }}" class="h-48 w-full rounded"></iframe>
                                            @else
                                                <div class="flex h-24 items-center justify-center rounded bg-gray-50 text-xs text-gray-500">Preview unavailable</div>
                                            @endif
                                            <div class="mt-1 truncate text-xs" title="{{ $image->original_name }}">{{ $image->original_name }}</div>
                                            @if(strtolower($image->mime_type ?? '') === 'application/pdf' || strtolower(pathinfo($image->original_name, PATHINFO_EXTENSION)) === 'pdf')
                                                <a href="{{ route('staff.results.images.view', $image) }}" target="_blank" rel="noopener" class="mr-2 text-xs text-primary hover:underline">Open PDF</a>
                                            @endif
                                            <a href="{{ route('staff.results.images.download', $image) }}" class="text-xs text-primary hover:underline">Download</a>
                                            @if(! $result->reported_at)
                                                <form method="POST" action="{{ route('staff.results.images.delete', $image) }}" onsubmit="return confirm('Remove this uploaded image?')" class="mt-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded border border-red-300 bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-100">Remove image</button>
                                                </form>
                                            @else
                                                <span class="mt-2 inline-block text-xs text-gray-500">Report released · removal locked</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-gray-500">Awaiting upload</span>
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
                        <td colspan="7" class="py-8 text-center text-gray-500">No paid investigations match these filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">@include('components.table-pagination', ['paginator' => $studies])</div>
    </div>
</div>
@endsection
