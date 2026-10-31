@extends('layouts.app')

@section('page-title', 'Staff Dashboard')

@section('content')
<div class="mb-6 flex flex-col gap-3 rounded-2xl bg-white p-5 shadow sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-lg font-bold text-primary">Result workflow</h2>
        <p class="text-sm text-gray-600">
            @if($staffType === 'radiographer')
                Open paid X-Ray investigations to upload images for radiologist review.
            @elseif($staffType === 'radiologist')
                Review assigned X-Ray images and complete radiology reports.
            @else
                Enter and manage results for non-X-Ray investigations.
            @endif
        </p>
    </div>
    <a href="{{ in_array($staffType, ['radiographer', 'radiologist'], true) ? route('staff.results.index') : route('staff.results.other') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-accent px-5 py-3 font-semibold text-white hover:bg-green-600">
        @if($staffType === 'radiographer')
            Open X-Ray Imaging Worklist
        @elseif($staffType === 'radiologist')
            Open X-Ray Reporting Worklist
        @else
            Open Result Reporting
        @endif
    </a>
</div>

<div class="grid md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Results Today</p>
        <p class="text-3xl font-bold text-primary mt-2">{{ $todayResults }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Results This Month</p>
        <p class="text-3xl font-bold text-secondary mt-2">{{ $monthResults }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Commission This Month</p>
        <p class="text-3xl font-bold text-accent mt-2">NGN {{ number_format($monthCommission, 2) }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl shadow lg:col-span-1">
        @if($staffType === 'radiologist')
            <h3 class="font-bold text-primary mb-2">Assigned Imaging</h3>
            <p class="text-sm text-gray-600 mb-4">Review the images sent to you and complete the radiology report.</p>
            <a href="{{ route('staff.results.index') }}" class="inline-block bg-accent text-white px-6 py-3 rounded-xl w-full text-center">Open Imaging Worklist</a>
            @if($pendingStudies->isNotEmpty())
                <div class="mt-4 space-y-2">
                    @foreach($pendingStudies as $study)
                        <a href="{{ route('staff.results.entry', $study->bill) }}" class="block rounded-lg bg-lightbg p-3 text-sm hover:bg-gray-100">
                            <span class="font-medium">{{ $study->bill->patient_name ?? 'Walk-in' }}</span>
                            <span class="text-gray-600">· {{ $study->billItem->service->name }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif

        <div class="{{ $staffType === 'radiologist' ? 'mt-6 border-t pt-5' : '' }}">
            <h3 class="font-bold text-primary mb-2">Find an Investigation</h3>
            <p class="text-sm text-gray-600 mb-4">All staff can use bill search for non-X-Ray results. X-Ray items remain in the assigned imaging workflow.</p>
            <form method="POST" action="{{ route('staff.results.lookup') }}" class="space-y-4">
                @csrf
                <label class="sr-only" for="dashboard-bill-number">Bill number</label>
                <input id="dashboard-bill-number" type="text" name="bill_no" value="{{ old('bill_no') }}"
                       placeholder="Example: B260719-1234" required
                       class="w-full rounded-xl border px-4 py-3 focus:border-accent focus:ring-accent">
                @error('bill_no')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <button class="w-full rounded-xl bg-accent px-6 py-3 font-semibold text-white">Search Investigation</button>
            </form>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow lg:col-span-2 overflow-x-auto">
        <h3 class="font-bold text-primary mb-4">Recent Results</h3>
        <table class="w-full text-sm">
            <thead class="border-b bg-lightbg">
                <tr>
                    <th class="py-3 px-4 text-left">Bill No</th>
                    <th class="py-3 px-4 text-left">Investigation</th>
                    <th class="py-3 px-4 text-left">Activity Date</th>
                    <th class="py-3 px-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentResults as $result)
                    <tr class="border-b">
                        <td class="py-3 px-4">{{ $result->bill->bill_no }}</td>
                        <td class="py-3 px-4">{{ $result->billItem->service->name }}</td>
                        <td class="py-3 px-4">{{ optional($result->performed_at ?? $result->reported_at ?? $result->completed_at)->format('d M Y h:i A') }}</td>
                        <td class="py-3 px-4 text-right">
                            @if($result->reported_at)
                                <a href="{{ route('staff.results.print', $result) }}" class="text-primary hover:underline">Print</a>
                            @else
                                <span class="text-gray-500">Awaiting report</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-gray-500">No result has been entered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
