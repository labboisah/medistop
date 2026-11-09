@extends('layouts.app')

@section('page-title', 'Enter Result')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white p-6 rounded-2xl shadow">
        <div class="flex flex-wrap gap-4 justify-between">
            <div>
                <h2 class="text-2xl font-bold text-primary">Bill {{ $bill->bill_no }}</h2>
                <p class="text-sm text-gray-500">Patient: {{ $bill->patient_name ?? 'Walk-in' }}</p>
            </div>
            <div class="text-sm text-gray-600">
                <p><strong>Status:</strong> {{ strtoupper($bill->payment_status) }}</p>
                <p><strong>Total Paid:</strong> NGN {{ number_format($bill->total_paid, 2) }}</p>
                <p><strong>Bill Date:</strong> {{ $bill->created_at->format('d M Y h:i A') }}</p>
            </div>
        </div>
    </div>

    @foreach($bill->items as $item)
        @php
            $result = $item->result;
            $isRadiographer = $staffType === 'radiographer';
            $usesXrayWorkflow = $isRadiographer && (bool) $item->service->is_xray;
            $isRadiologistXray = $staffType === 'radiologist' && (bool) $item->service->is_xray;
            $reportingDeadline = $result?->performed_at?->copy()->addHours(24)
                ?? $result?->created_at?->copy()->addHours(24);
            $lockedByOtherReporter = $result && $result->reported_by && $result->reported_by !== auth()->id();
        @endphp

        <div id="bill-item-{{ $item->id }}" class="bg-white p-6 rounded-2xl shadow">
            <div class="flex flex-wrap gap-3 justify-between mb-5">
                <div>
                    <h3 class="text-xl font-bold text-primary">{{ $item->service->name }}</h3>
                    <p class="text-sm text-gray-500">{{ optional($item->service->category)->name }}</p>
                </div>
                <div class="flex flex-col gap-2 text-right">
                    @if($result?->performed_by)
                        <span class="px-3 py-1 rounded-full text-xs bg-blue-600 text-white self-start">
                            Performed by {{ optional($result->performer)->name ?? 'Unknown staff' }}
                        </span>
                    @endif

                    @if($result?->reported_by)
                        <span class="px-3 py-1 rounded-full text-xs bg-accent text-white self-start">
                            Reported by {{ optional($result->reporter)->name ?? 'Unknown staff' }}
                        </span>
                    @endif

                    @if($result?->radiologist_id)
                        <span class="px-3 py-1 rounded-full text-xs bg-purple-700 text-white self-start">
                            Assigned to {{ optional($result->assignedRadiologist)->name ?? 'Radiologist' }}
                        </span>
                    @endif

                    @if($isRadiologistXray && ! $result?->reported_at && $reportingDeadline)
                        <span data-report-countdown="{{ $reportingDeadline->timestamp * 1000 }}" class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">
                            Time remaining: calculating…
                        </span>
                    @endif
                </div>
            </div>

            @if($result?->images->isNotEmpty())
                <div class="mb-6 rounded-xl bg-lightbg p-4">
                    <h4 class="font-semibold text-primary mb-3">Uploaded imaging</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($result->images as $image)
                                @php($isPdf = strtolower($image->mime_type ?? '') === 'application/pdf' || strtolower(pathinfo($image->original_name, PATHINFO_EXTENSION)) === 'pdf')
                            <div class="bg-white rounded-lg border p-3">
                                @if(str_starts_with(strtolower($image->mime_type ?? ''), 'image/'))
                                    <a href="{{ route('staff.results.images.view', $image) }}" target="_blank" rel="noopener">
                                        <img src="{{ route('staff.results.images.view', $image) }}" alt="{{ $image->original_name }}" class="w-full h-48 object-contain rounded">
                                    </a>
                                    @elseif($isPdf)
                                        <iframe src="{{ route('staff.results.images.view', $image) }}#page=1&view=Fit&toolbar=0&navpanes=0" title="PDF content preview: {{ $image->original_name }}" class="h-48 w-full rounded"></iframe>
                                @else
                                    <div class="h-48 flex items-center justify-center text-sm text-gray-600">{{ $image->original_name }}</div>
                                @endif
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <span class="truncate" title="{{ $image->original_name }}">{{ $image->original_name }}</span>
                                        @if($isPdf)
                                            <a href="{{ route('staff.results.images.view', $image) }}" target="_blank" rel="noopener" class="shrink-0 text-primary hover:underline">Open</a>
                                        @endif
                                    <a href="{{ route('staff.results.images.download', $image) }}" class="shrink-0 text-primary hover:underline">Download</a>
                                </div>
                                @if($usesXrayWorkflow && ! $result?->reported_at && (int) $result?->performed_by === (int) auth()->id())
                                    <form method="POST" action="{{ route('staff.results.images.delete', $image) }}" onsubmit="return confirm('Remove this uploaded image?')" class="mt-3">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 font-semibold text-red-700 hover:bg-red-100">Remove image</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($usesXrayWorkflow)
                @if($result?->performed_by && $result->performed_by !== auth()->id())
                    <div class="bg-lightbg p-4 rounded-xl text-sm text-gray-600">
                        This investigation has been marked as performed by {{ optional($result->performer)->name ?? 'another staff member' }}.
                    </div>
                @elseif($result?->reported_at)
                    <div class="bg-green-50 p-4 rounded-xl text-sm text-green-800 mb-4">
                        The radiologist has completed this report. You can now print it.
                    </div>
                    <a href="{{ route('staff.results.print', $result) }}" class="inline-block bg-primary text-white px-6 py-3 rounded-xl">Print Completed Report</a>
                @else
                    <form method="POST" enctype="multipart/form-data" action="{{ route('staff.results.store', $bill) }}" class="space-y-5">
                        @csrf
                        <input type="hidden" name="bill_item_id" value="{{ $item->id }}">

                        @if($result?->performed_by)
                            <div class="bg-lightbg p-4 rounded-xl text-sm text-gray-600 mb-4">
                                This investigation has been marked as performed by {{ optional($result->performer)->name ?? 'you' }}.
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium mb-2">Send images to radiologist</label>
                            <select name="radiologist_id" required class="w-full px-4 py-3 border rounded-xl">
                                <option value="">Choose radiologist</option>
                                @foreach($radiologists as $radiologist)
                                    <option value="{{ $radiologist->id }}" @selected(old('radiologist_id', $result?->radiologist_id) == $radiologist->id)>
                                        {{ $radiologist->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if($radiologists->isEmpty())
                                <p class="text-amber-700 text-sm mt-2">No radiologist account is available. Ask an administrator to add a radiologist before sending this study.</p>
                            @endif
                            @error('radiologist_id')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Images, PDFs, or DICOM files</label>
                            <input type="file" name="images[]" multiple required accept="image/*,application/pdf,.pdf,.dcm,.dicom,.zip" data-imaging-preview="imaging-preview-{{ $item->id }}" class="w-full px-4 py-3 border rounded-xl">
                            <p class="text-xs text-gray-500 mt-2">Upload image files, PDFs, DICOM files, or DICOM ZIP archives (up to 500 MB per file).</p>
                            <div id="imaging-preview-{{ $item->id }}" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3" style="display: none"></div>
                            @foreach($errors->getMessages() as $field => $messages)
                                @if(str_starts_with($field, 'images.'))
                                    @foreach($messages as $message)<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@endforeach
                                @endif
                            @endforeach
                        </div>

                        <button class="bg-accent text-white px-6 py-3 rounded-xl">
                            {{ $result?->performed_by === auth()->id() ? 'Send Update to Radiologist' : 'Send Imaging to Radiologist' }}
                        </button>
                    </form>
                @endif
            @elseif($isRadiologistXray && $result?->reported_at && (! request()->boolean('edit') || (int) ($result->print_count ?? 0) > 0))
                <div class="mb-4 rounded-xl bg-green-50 p-4 text-sm text-green-800">
                    <p class="font-semibold">Report released {{ $result->reported_at->format('d M Y h:i A') }}.</p>
                    <p class="mt-1">Print count: {{ $result->print_count ?? 0 }}</p>
                </div>
                <a href="{{ route('staff.results.print', $result) }}" class="inline-block rounded-xl bg-primary px-6 py-3 text-white">View and print released report</a>
                @if((int) ($result->print_count ?? 0) === 0)
                    <a href="{{ route('staff.results.entry', ['bill' => $bill, 'edit' => 1]) }}" class="ml-2 inline-block rounded-xl bg-amber-600 px-6 py-3 text-white">Edit Report</a>
                @endif
            @elseif($lockedByOtherReporter)
                <div class="bg-lightbg p-4 rounded-xl text-sm text-gray-600">
                    This result has already been reported by {{ optional($result->reporter)->name ?? 'another staff member' }}.
                </div>
            @else
                <form method="POST" action="{{ route('staff.results.store', $bill) }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="bill_item_id" value="{{ $item->id }}">

                    @if($isRadiologistXray && $result?->reported_at && request()->boolean('edit') && (int) ($result->print_count ?? 0) === 0)
                        <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">
                            Editing a released report. Changes will be saved to the released report.
                            <a href="{{ route('staff.results.entry', $bill) }}" class="ml-2 font-semibold underline">Cancel editing</a>
                        </div>
                    @endif

                    @if($isRadiologistXray && $result?->status === 'draft')
                        <div class="rounded-xl bg-blue-50 p-4 text-sm text-blue-800">
                            Draft saved {{ $result->updated_at?->format('d M Y h:i A') }}. Continue editing, save another draft, or release the report when ready.
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium mb-2">Result Template</label>
                        <input type="search" class="template-select w-full px-4 py-3 border rounded-xl"
                               data-item="{{ $item->id }}" list="template-options-{{ $item->id }}"
                               placeholder="Search saved templates..." autocomplete="off">
                        <datalist id="template-options-{{ $item->id }}">
                            @foreach($templates as $template)
                                <option value="{{ $template->name }}" data-template-id="{{ $template->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Clinical Note</label>
                        <textarea id="clinical_note_{{ $item->id }}" name="clinical_note" rows="3" class="w-full px-4 py-3 border rounded-xl">{{ old('clinical_note', $result?->clinical_note) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Findings</label>
                        <textarea id="findings_{{ $item->id }}" name="findings" rows="7" class="w-full px-4 py-3 border rounded-xl" @unless($isRadiologistXray) required @endunless>{{ old('findings', $result?->findings) }}</textarea>
                        @error('findings')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Impression</label>
                        <textarea id="impression_{{ $item->id }}" name="impression" rows="4" class="w-full px-4 py-3 border rounded-xl">{{ old('impression', $result?->impression) }}</textarea>
                    </div>

                    <div class="bg-lightbg p-4 rounded-xl space-y-3">
                        <label class="inline-flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" name="save_template" value="1" class="rounded">
                            Save this result text as a template
                        </label>

                        <input type="text" name="template_name"
                               placeholder="Template name"
                               class="w-full px-4 py-3 border rounded-xl">

                        @error('template_name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @if($isRadiologistXray && ! ($result?->reported_at && request()->boolean('edit')))
                            <button type="submit" name="report_action" value="draft" class="rounded-xl bg-gray-600 px-6 py-3 text-white hover:bg-gray-700">Save as Draft</button>
                        @endif
                        @if($isRadiologistXray)
                            <button type="submit" name="report_action" value="release" class="rounded-xl bg-accent px-6 py-3 text-white hover:bg-green-600">{{ $result?->reported_at ? 'Save Report Changes' : 'Release Report' }}</button>
                        @else
                            <button class="bg-accent text-white px-6 py-3 rounded-xl">Save and Print</button>
                        @endif
                        @if($result && ! $isRadiologistXray)
                            <a href="{{ route('staff.results.print', $result) }}"
                               class="bg-primary text-white px-6 py-3 rounded-xl">Print Existing</a>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    @endforeach
</div>
@endsection

@section('scripts')
<script>
const resultTemplates = @js($templatePayload);

document.addEventListener('DOMContentLoaded', () => {
    const updateReportCountdowns = () => {
        document.querySelectorAll('[data-report-countdown]').forEach((clock) => {
            let seconds = Math.floor((Number(clock.dataset.reportCountdown) - Date.now()) / 1000);
            const overdue = seconds < 0;
            seconds = Math.abs(seconds);
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainingSeconds = seconds % 60;
            const time = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
            clock.textContent = overdue ? `Overdue by ${time}` : `Time remaining: ${time}`;
            clock.classList.toggle('bg-red-100', overdue);
            clock.classList.toggle('text-red-800', overdue);
            clock.classList.toggle('bg-amber-100', !overdue);
            clock.classList.toggle('text-amber-900', !overdue);
        });
    };

    updateReportCountdowns();
    window.setInterval(updateReportCountdowns, 1000);

    document.querySelectorAll('[data-imaging-preview]').forEach((input) => {
        const preview = document.getElementById(input.dataset.imagingPreview);
        let previewUrls = [];

        input.addEventListener('change', () => {
            previewUrls.forEach((url) => URL.revokeObjectURL(url));
            previewUrls = [];
            preview.replaceChildren();

            Array.from(input.files || []).forEach((file) => {
                const card = document.createElement('div');
                card.className = 'min-w-0 overflow-hidden rounded-lg border bg-white p-2';

                const filename = document.createElement('p');
                filename.className = 'mb-2 truncate text-xs text-gray-600';
                filename.textContent = file.name;
                card.appendChild(filename);

                const objectUrl = URL.createObjectURL(file);
                previewUrls.push(objectUrl);

                if (file.type.startsWith('image/')) {
                    const image = document.createElement('img');
                    image.src = objectUrl;
                    image.alt = `Preview of ${file.name}`;
                    image.className = 'h-40 w-full rounded object-contain';
                    card.appendChild(image);
                } else if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
                    const pdf = document.createElement('iframe');
                    pdf.src = `${objectUrl}#view=Fit&toolbar=0&navpanes=0`;
                    pdf.title = `PDF preview of ${file.name}`;
                    pdf.className = 'h-40 w-full rounded border-0';
                    card.appendChild(pdf);
                } else {
                    const fileType = document.createElement('p');
                    fileType.className = 'flex h-40 items-center justify-center rounded bg-gray-50 text-center text-sm text-gray-500';
                    fileType.textContent = 'Preview unavailable for this file type';
                    card.appendChild(fileType);
                }

                preview.appendChild(card);
            });

            preview.style.display = input.files?.length ? 'grid' : 'none';
        });
    });

    document.querySelectorAll('.template-select').forEach((input) => {
        const applyTemplate = () => {
            const options = document.getElementById(`template-options-${input.dataset.item}`);
            const option = Array.from(options.options).find((candidate) => candidate.value === input.value);
            const template = option ? resultTemplates[String(option.dataset.templateId)] : null;
            const itemId = input.dataset.item;

            if (!template) {
                return;
            }

            document.getElementById(`clinical_note_${itemId}`).value = template.clinical_note || '';
            document.getElementById(`findings_${itemId}`).value = template.findings || '';
            document.getElementById(`impression_${itemId}`).value = template.impression || '';
        };

        input.addEventListener('change', applyTemplate);
    });
});
</script>
@endsection
