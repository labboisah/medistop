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
                    <th class="py-3 px-4 text-left">Images</th>
                    <th class="py-3 px-4 text-left">Date</th>
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
                        <td class="py-3 px-4">{{ $study->images->count() }}</td>
                        <td class="py-3 px-4">{{ optional($study->performed_at)->format('d M Y h:i A') ?? '—' }}</td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('staff.results.entry', $study->bill) }}" class="inline-block rounded-lg bg-accent px-4 py-2 text-white">Review and report</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No studies are awaiting your report.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">@include('components.table-pagination', ['paginator' => $studies])</div>
    </div>
</div>
@endsection
