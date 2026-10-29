<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillResult;
use App\Models\BillResultImage;
use App\Models\ResultTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StaffResultController extends Controller
{
    public function dashboard()
    {
        $staffType = auth()->user()->staff_type ?? 'staff';
        $dateColumn = $this->activityDateColumn();

        $todayResults = $this->activityQuery()
            ->whereDate($dateColumn, today())
            ->count();

        $monthResults = $this->activityQuery()
            ->whereMonth($dateColumn, now()->month)
            ->whereYear($dateColumn, now()->year)
            ->count();

        $monthCommission = $this->commissionQuery()
            ->whereMonth($dateColumn, now()->month)
            ->whereYear($dateColumn, now()->year)
            ->sum($this->commissionColumn());

        $recentResults = $this->activityQuery()
            ->with('bill', 'billItem.service')
            ->latest($dateColumn)
            ->limit(5)
            ->get();

        $pendingStudies = $staffType === 'radiologist'
            ? BillResult::with(['bill', 'billItem.service', 'performer', 'images'])
                ->where('radiologist_id', auth()->id())
                ->whereNull('reported_at')
                ->latest('performed_at')
                ->limit(5)
                ->get()
            : collect();

        return view('staff.dashboard', compact(
            'todayResults',
            'monthResults',
            'monthCommission',
            'recentResults',
            'pendingStudies',
            'staffType'
        ));
    }

    public function index()
    {
        if ((auth()->user()->staff_type ?? 'staff') === 'radiologist') {
            $studies = BillResult::with(['bill', 'billItem.service', 'performer', 'images'])
                ->where('radiologist_id', auth()->id())
                ->whereNull('reported_at')
                ->latest('performed_at')
                ->paginate(20)
                ->withQueryString();

            return view('staff.results.worklist', compact('studies'));
        }

        return view('staff.results.index');
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'bill_no' => 'required|string',
        ]);

        $bill = Bill::where('bill_no', $request->bill_no)->first();

        if (! $bill) {
            return back()->withInput()->withErrors(['bill_no' => 'Bill number was not found.']);
        }

        if (! $this->billIsPaid($bill)) {
            return back()->withInput()->withErrors(['bill_no' => 'Only fully paid bills are allowed for result entry.']);
        }

        return redirect()->route('staff.results.entry', $bill);
    }

    public function entry(Bill $bill)
    {
        $this->ensurePaidBill($bill);

        $bill->load([
            'items.service.category',
            'items.result.performer',
            'items.result.reporter',
            'items.result.assignedRadiologist',
            'items.result.images',
            'user',
            'payments.user',
        ]);

        $staffType = auth()->user()->staff_type ?? 'staff';
        if ($staffType === 'radiologist') {
            $assignedItems = $bill->items->filter(fn ($item) =>
                (int) ($item->result?->radiologist_id ?? 0) === (int) auth()->id()
            )->values();

            abort_if($assignedItems->isEmpty(), 403);
            $bill->setRelation('items', $assignedItems);
        }

        $templates = ResultTemplate::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();
        $templatePayload = $templates->mapWithKeys(fn ($template) => [
            $template->id => [
                'clinical_note' => $template->clinical_note,
                'findings' => $template->findings,
                'impression' => $template->impression,
            ],
        ])->toArray();

        $radiologists = $staffType === 'radiographer'
            ? \App\Models\User::where('role', 'staff')->where('staff_type', 'radiologist')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('staff.results.entry', compact('bill', 'templates', 'templatePayload', 'staffType', 'radiologists'));
    }

    public function store(Request $request, Bill $bill)
    {
        $this->ensurePaidBill($bill);

        $staffType = auth()->user()->staff_type ?? 'staff';

        $validated = $request->validate([
            'bill_item_id' => 'required|exists:bill_items,id',
            'clinical_note' => 'nullable|string',
            'findings' => $staffType === 'radiographer' ? 'nullable|string' : 'required|string',
            'impression' => 'nullable|string',
            'save_template' => 'nullable|boolean',
            'template_name' => 'required_if:save_template,1|nullable|string|max:255',
            'radiologist_id' => $staffType === 'radiographer' ? 'required|integer|exists:users,id' : 'nullable|integer',
            'images' => $staffType === 'radiographer' ? 'required|array|min:1' : 'nullable|array',
            'images.*' => [
                'file',
                'max:512000',
                function ($attribute, $file, $fail) {
                    if (! $file instanceof \Illuminate\Http\UploadedFile) {
                        return;
                    }

                    $extension = strtolower($file->getClientOriginalExtension());
                    $mimeType = strtolower($file->getMimeType() ?? '');
                    $isImage = str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml';
                    $isDicom = in_array($extension, ['dcm', 'dicom'], true)
                        || in_array($mimeType, ['application/dicom', 'application/dicom+json'], true);
                    $isDicomArchive = $extension === 'zip'
                        || in_array($mimeType, ['application/zip', 'application/x-zip-compressed'], true);

                    if (! $isImage && ! $isDicom && ! $isDicomArchive) {
                        $fail('Upload an image, DICOM file, or DICOM ZIP archive.');
                    }
                },
            ],
        ]);

        $billItem = $bill->items()->whereKey($validated['bill_item_id'])->firstOrFail();

        $existingResult = BillResult::where('bill_item_id', $billItem->id)->first();

        if ($staffType === 'radiographer') {
            if ($existingResult?->reported_at) {
                abort(403, 'This investigation already has a completed radiologist report.');
            }

            if ($existingResult?->performed_by && $existingResult->performed_by !== auth()->id()) {
                abort(403, 'This investigation has already been performed by another radiographer.');
            }

            $radiologist = \App\Models\User::where('role', 'staff')
                ->where('staff_type', 'radiologist')
                ->find($validated['radiologist_id']);

            if (! $radiologist) {
                throw ValidationException::withMessages([
                    'radiologist_id' => 'Choose an active radiologist account.',
                ]);
            }

            $result = BillResult::updateOrCreate(
                ['bill_item_id' => $billItem->id],
                [
                    'bill_id' => $bill->id,
                    'staff_id' => $existingResult?->staff_id ?? auth()->id(),
                    'performed_by' => auth()->id(),
                    'performed_at' => $existingResult?->performed_at ?? now(),
                    'radiologist_id' => $radiologist->id,
                    'findings' => $existingResult?->findings ?? '',
                    'status' => 'pending_report',
                ]
            );

            foreach ($request->file('images', []) as $image) {
                $path = $image->store("radiology-images/{$result->id}", 'local');

                BillResultImage::create([
                    'bill_result_id' => $result->id,
                    'uploaded_by' => auth()->id(),
                    'path' => $path,
                    'original_name' => substr($image->getClientOriginalName(), 0, 255),
                    'mime_type' => $image->getMimeType() ?: $image->getClientMimeType(),
                    'file_size' => $image->getSize() ?: 0,
                ]);
            }

            return redirect()
                ->route('staff.results.entry', $bill)
                ->with('success', "Investigation sent to {$radiologist->name} for reporting.");
        }

        if ($staffType === 'radiologist' && (int) ($existingResult?->radiologist_id ?? 0) !== (int) auth()->id()) {
            abort(403, 'This investigation is not assigned to your account.');
        }

        if ($existingResult?->radiologist_id && $staffType !== 'radiologist') {
            abort(403, 'Only the assigned radiologist can report on this investigation.');
        }

        if ($existingResult && $existingResult->reported_by && $existingResult->reported_by !== auth()->id()) {
            abort(403, 'This investigation already has a report entered by another staff member.');
        }

        $result = BillResult::updateOrCreate(
            ['bill_item_id' => $billItem->id],
            [
                'bill_id' => $bill->id,
                'staff_id' => auth()->id(),
                'reported_by' => auth()->id(),
                'clinical_note' => $validated['clinical_note'] ?? null,
                'findings' => $validated['findings'],
                'impression' => $validated['impression'] ?? null,
                'status' => 'completed',
                'reported_at' => now(),
                'completed_at' => now(),
            ]
        );

        if ($request->boolean('save_template')) {
            ResultTemplate::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'name' => $validated['template_name'],
                ],
                [
                    'clinical_note' => $validated['clinical_note'] ?? null,
                    'findings' => $validated['findings'],
                    'impression' => $validated['impression'] ?? null,
                ]
            );
        }

        if ($staffType === 'radiologist') {
            return redirect()
                ->route('staff.results.index')
                ->with('success', 'Report saved. The radiographer can now print it.');
        }

        return redirect()->route('staff.results.print', $result)->with('success', 'Result saved successfully.');
    }

    public function viewImage(BillResultImage $image)
    {
        $this->authorizeImageAccess($image);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($image->path), 404);

        if (! str_starts_with(strtolower($image->mime_type ?? ''), 'image/')) {
            return $this->downloadImage($image);
        }

        return response()->file($disk->path($image->path), [
            'Content-Type' => $image->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadImage(BillResultImage $image)
    {
        $this->authorizeImageAccess($image);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($image->path), 404);

        return $disk->download($image->path, $image->original_name);
    }

    public function reports(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $dateColumn = $this->activityDateColumn();

        $results = $this->activityQuery()
            ->with('bill', 'billItem.service', 'performer', 'reporter', 'staff')
            ->when($from, fn ($query) => $query->whereDate($dateColumn, '>=', $from))
            ->when($to, fn ($query) => $query->whereDate($dateColumn, '<=', $to))
            ->latest($dateColumn)
            ->paginate(15)
            ->withQueryString();

        $staffType = auth()->user()->staff_type ?? 'staff';

        return view('staff.results.reports', compact('results', 'from', 'to', 'staffType'));
    }

    public function commission(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $commissionColumn = $this->commissionColumn();
        $dateColumn = $this->activityDateColumn();
        $staffType = auth()->user()->staff_type ?? 'staff';

        $results = $this->commissionQuery()
            ->select([
                'bill_results.*',
                'bill_items.price as bill_amount',
                $commissionColumn . ' as commission_amount',
            ])
            ->when($from, fn ($query) => $query->whereDate($dateColumn, '>=', $from))
            ->when($to, fn ($query) => $query->whereDate($dateColumn, '<=', $to))
            ->with('bill', 'billItem.service')
            ->latest($dateColumn)
            ->paginate(15)
            ->withQueryString();

        $todayCommission = $this->commissionQuery()
            ->whereDate($dateColumn, today())
            ->sum($commissionColumn);

        $monthCommission = $this->commissionQuery()
            ->whereMonth($dateColumn, now()->month)
            ->whereYear($dateColumn, now()->year)
            ->sum($commissionColumn);

        $filteredCommission = $this->commissionQuery()
            ->when($from, fn ($query) => $query->whereDate($dateColumn, '>=', $from))
            ->when($to, fn ($query) => $query->whereDate($dateColumn, '<=', $to))
            ->sum($commissionColumn);

        $filteredBillAmount = $this->commissionQuery()
            ->when($from, fn ($query) => $query->whereDate($dateColumn, '>=', $from))
            ->when($to, fn ($query) => $query->whereDate($dateColumn, '<=', $to))
            ->sum('bill_items.price');

        return view('staff.results.commission', compact(
            'results',
            'todayCommission',
            'monthCommission',
            'filteredCommission',
            'filteredBillAmount',
            'from',
            'to',
            'staffType'
        ));
    }

    public function print(BillResult $result)
    {
        $isReporter = $result->reported_by === auth()->id();
        $isPerformingRadiographer = (auth()->user()->staff_type ?? null) === 'radiographer'
            && $result->performed_by === auth()->id();

        if (! $result->reported_at || (! $isReporter && ! $isPerformingRadiographer)) {
            abort(403);
        }

        $result->load([
            'bill.user',
            'bill.payments.user',
            'billItem.service.category',
            'performer',
            'reporter',
            'staff',
            'images',
        ]);

        return view('staff.results.print', compact('result'));
    }

    private function authorizeImageAccess(BillResultImage $image): void
    {
        $result = $image->result;
        $staffType = auth()->user()->staff_type ?? 'staff';

        $isAssignedRadiologist = $staffType === 'radiologist'
            && (int) $result->radiologist_id === (int) auth()->id();
        $isPerformingRadiographer = $staffType === 'radiographer'
            && $result->performed_by === auth()->id();

        abort_unless($isAssignedRadiologist || $isPerformingRadiographer, 403);
    }

    private function ensurePaidBill(Bill $bill): void
    {
        if (! $this->billIsPaid($bill)) {
            abort(403, 'Only fully paid bills are allowed for result entry.');
        }
    }

    private function billIsPaid(Bill $bill): bool
    {
        return $bill->payment_status === 'paid' || (float) $bill->balance <= 0;
    }

    private function commissionQuery()
    {
        return $this->activityQuery()
            ->join('bill_items', 'bill_results.bill_item_id', '=', 'bill_items.id')
            ->leftJoin('revenue_distributions', 'bill_items.id', '=', 'revenue_distributions.bill_item_id');
    }

    private function activityQuery()
    {
        return BillResult::query()->where(function ($query) {
            if ((auth()->user()->staff_type ?? 'staff') === 'radiographer') {
                $query->where('bill_results.performed_by', auth()->id());
                return;
            }

            $query->where('bill_results.reported_by', auth()->id())
                ->orWhere(function ($fallback) {
                    $fallback->whereNull('bill_results.reported_by')
                        ->where('bill_results.staff_id', auth()->id());
                });
        });
    }

    private function commissionColumn(): string
    {
        return match (auth()->user()->staff_type ?? 'staff') {
            'radiologist' => 'revenue_distributions.radiologist_amount',
            'radiographer' => 'revenue_distributions.radiographer_amount',
            default => 'revenue_distributions.staff_amount',
        };
    }

    private function activityDateColumn(): string
    {
        return (auth()->user()->staff_type ?? 'staff') === 'radiographer'
            ? 'bill_results.performed_at'
            : 'bill_results.reported_at';
    }

    private function dateRange(Request $request): array
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        if (
            ! empty($validated['from'])
            && ! empty($validated['to'])
            && $validated['to'] < $validated['from']
        ) {
            throw ValidationException::withMessages([
                'to' => 'The to date must be after or equal to the from date.',
            ]);
        }

        return [
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        ];
    }
}
