<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillResult;
use App\Models\BillResultImage;
use App\Models\ResultTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

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
                ->whereHas('billItem.service', fn ($service) => $service->where('is_xray', true))
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

    public function index(Request $request)
    {
        $staffType = auth()->user()->staff_type ?? 'staff';
        if ($staffType === 'radiographer') {
            $filters = $request->validate([
                'search' => 'nullable|string|max:255',
                'from' => 'nullable|date',
                'to' => 'nullable|date',
                'status' => 'nullable|in:awaiting_upload,awaiting_report,report_ready',
            ]);
            $search = trim($filters['search'] ?? '');
            $from = $filters['from'] ?? '';
            $to = $filters['to'] ?? '';
            $status = $filters['status'] ?? '';
            $radiographerId = auth()->id();

            $studies = BillItem::query()
                ->with(['bill', 'service', 'result.images', 'result.assignedRadiologist'])
                ->whereHas('service', fn ($service) => $service->where('is_xray', true))
                ->whereHas('bill', function ($bill) use ($from, $to) {
                    $bill->where(function ($paid) {
                        $paid->where('payment_status', 'paid')->orWhere('balance', '<=', 0);
                    });
                    if ($from !== '') {
                        $bill->where('created_at', '>=', Carbon::parse($from)->startOfDay());
                    }
                    if ($to !== '') {
                        $bill->where('created_at', '<', Carbon::parse($to)->addDay()->startOfDay());
                    }
                })
                ->where(function ($query) use ($radiographerId) {
                    $query->whereDoesntHave('result')
                        ->orWhereHas('result', fn ($result) => $result->where('performed_by', $radiographerId));
                })
                ->when($status === 'awaiting_upload', fn ($query) => $query->whereDoesntHave('result'))
                ->when($status === 'awaiting_report', fn ($query) => $query->whereHas('result', fn ($result) => $result
                    ->where('performed_by', $radiographerId)->whereNull('reported_at')))
                ->when($status === 'report_ready', fn ($query) => $query->whereHas('result', fn ($result) => $result
                    ->where('performed_by', $radiographerId)->whereNotNull('reported_at')))
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->whereHas('bill', fn ($bill) => $bill
                        ->where('bill_no', 'like', "%{$search}%")
                        ->orWhere('patient_name', 'like', "%{$search}%"))
                        ->orWhereHas('service', fn ($service) => $service->where('name', 'like', "%{$search}%"));
                }))
                ->latest('bill_items.created_at')
                ->paginate(15)
                ->withQueryString();

            return view('staff.results.radiographer-worklist', compact('studies', 'search', 'from', 'to', 'status'));
        }

        if ($staffType === 'radiologist') {
            $filters = $request->validate([
                'search' => 'nullable|string|max:255',
                'from' => 'nullable|date',
                'to' => 'nullable|date',
            ]);
            $search = trim($filters['search'] ?? '');
            $from = $filters['from'] ?? '';
            $to = $filters['to'] ?? '';
            $studies = BillResult::with(['bill', 'billItem.service', 'performer', 'images'])
                ->where('radiologist_id', auth()->id())
                ->whereHas('billItem.service', fn ($service) => $service->where('is_xray', true))
                ->whereNull('reported_at')
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->whereHas('bill', fn ($bill) => $bill->where('patient_name', 'like', "%{$search}%")
                        ->orWhere('bill_no', 'like', "%{$search}%"))
                        ->orWhereHas('billItem.service', fn ($service) => $service->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('performer', fn ($performer) => $performer->where('name', 'like', "%{$search}%"));
                }))
                ->when($from !== '', fn ($query) => $query->where('performed_at', '>=', Carbon::parse($from)->startOfDay()))
                ->when($to !== '', fn ($query) => $query->where('performed_at', '<', Carbon::parse($to)->addDay()->startOfDay()))
                ->latest('performed_at')
                ->paginate(20)
                ->withQueryString();

            return view('staff.results.worklist', compact('studies', 'search', 'from', 'to'));
        }

        return view('staff.results.index');
    }

    public function otherResults()
    {
        return view('staff.results.other-index');
    }

    public function lookup(Request $request)
    {
        return $this->processBillLookup($request, false);
    }

    public function lookupOther(Request $request)
    {
        return $this->processBillLookup($request, true);
    }

    private function processBillLookup(Request $request, bool $otherResultsOnly)
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

        $staffType = auth()->user()->staff_type ?? 'staff';
        $eligibleItems = $bill->items()
            ->where(function ($items) use ($staffType, $otherResultsOnly) {
                $items->whereHas('service', fn ($service) => $service->where('is_xray', false));

                if (! $otherResultsOnly && $staffType === 'radiographer') {
                    $items->orWhere(function ($xrayItems) {
                        $xrayItems->whereHas('service', fn ($service) => $service->where('is_xray', true))
                            ->where(function ($work) {
                            $work->whereDoesntHave('result')
                                ->orWhereHas('result', fn ($result) => $result->where('performed_by', auth()->id()));
                        });
                    });
                } elseif (! $otherResultsOnly && $staffType === 'radiologist') {
                    $items->orWhere(function ($xrayItems) {
                        $xrayItems->whereHas('service', fn ($service) => $service->where('is_xray', true))
                            ->whereHas('result', fn ($result) => $result->where('radiologist_id', auth()->id()));
                    });
                }
            })
            ->exists();

        if (! $eligibleItems) {
            $message = $otherResultsOnly
                ? 'This bill has no non-X-Ray investigation available for direct result entry.'
                : 'This bill has no non-X-Ray investigation for direct result entry or X-Ray work available to your account.';
            return back()->withInput()->withErrors(['bill_no' => $message]);
        }

        return redirect()->route('staff.results.entry', $otherResultsOnly
            ? ['bill' => $bill, 'workflow' => 'other']
            : ['bill' => $bill]);
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
        if (request()->query('workflow') === 'other') {
            $otherItems = $bill->items->reject(fn ($item) => $item->service->is_xray)->values();
            abort_if($otherItems->isEmpty(), 403, 'This bill has no non-X-Ray investigation available for direct result entry.');
            $bill->setRelation('items', $otherItems);
        } elseif ($staffType === 'radiologist') {
            $assignedItems = $bill->items->filter(fn ($item) =>
                ! $item->service->is_xray
                || (int) ($item->result?->radiologist_id ?? 0) === (int) auth()->id()
            )->values();

            abort_if($assignedItems->isEmpty(), 403, 'No non-X-Ray investigation or X-Ray study assigned to you was found on this bill.');
            $bill->setRelation('items', $assignedItems);
        } else {
            $reportableItems = $staffType === 'radiographer'
                ? $bill->items
                : $bill->items->reject(fn ($item) => $item->service->is_xray)->values();
            abort_if($reportableItems->isEmpty(), 403, 'X-Ray results must be reported by the assigned radiologist.');
            $bill->setRelation('items', $reportableItems);
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
        $request->validate(['bill_item_id' => 'required|integer|exists:bill_items,id']);
        $billItem = $bill->items()->with('service')->whereKey($request->integer('bill_item_id'))->firstOrFail();
        $isXray = (bool) $billItem->service->is_xray;

        $usesXrayWorkflow = $isXray && $staffType === 'radiographer';
        if ($isXray && ! $usesXrayWorkflow && $staffType !== 'radiologist') {
            abort(403, 'X-Ray results must be handled through the radiographer and assigned radiologist workflow.');
        }

        $validated = $request->validate([
            'bill_item_id' => 'required|integer|exists:bill_items,id',
            'clinical_note' => 'nullable|string',
            'findings' => $usesXrayWorkflow ? 'nullable|string' : 'required|string',
            'impression' => 'nullable|string',
            'save_template' => 'nullable|boolean',
            'template_name' => 'required_if:save_template,1|nullable|string|max:255',
            'radiologist_id' => $usesXrayWorkflow ? 'required|integer|exists:users,id' : 'prohibited',
            'images' => $usesXrayWorkflow ? 'required|array|min:1' : 'prohibited',
            'images.*' => $usesXrayWorkflow ? [
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
            ] : 'prohibited',
        ]);

        $existingResult = BillResult::where('bill_item_id', $billItem->id)->first();

        if ($usesXrayWorkflow) {
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

        if ($isXray && $staffType === 'radiologist' && (int) ($existingResult?->radiologist_id ?? 0) !== (int) auth()->id()) {
            abort(403, 'This investigation is not assigned to your account.');
        }

        if ($isXray && $existingResult?->radiologist_id && $staffType !== 'radiologist') {
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
        $search = trim((string) $request->query('search', ''));
        $dateColumn = $this->activityDateColumn();

        $results = $this->activityQuery()
            ->with('bill', 'billItem.service', 'performer', 'reporter', 'staff')
            ->when($from, fn ($query) => $query->whereDate($dateColumn, '>=', $from))
            ->when($to, fn ($query) => $query->whereDate($dateColumn, '<=', $to))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->whereHas('bill', fn ($bill) => $bill->where('bill_no', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%"))
                    ->orWhereHas('billItem.service', fn ($service) => $service->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('performer', fn ($performer) => $performer->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('reporter', fn ($reporter) => $reporter->where('name', 'like', "%{$search}%"));
            }))
            ->latest($dateColumn)
            ->paginate(15)
            ->withQueryString();

        $staffType = auth()->user()->staff_type ?? 'staff';

        return view('staff.results.reports', compact('results', 'from', 'to', 'staffType', 'search'));
    }

    public function commission(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $search = trim((string) $request->query('search', ''));
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
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->whereHas('bill', fn ($bill) => $bill->where('bill_no', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%"))
                    ->orWhereHas('billItem.service', fn ($service) => $service->where('name', 'like', "%{$search}%"));
            }))
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
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->whereHas('bill', fn ($bill) => $bill->where('bill_no', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%"))
                    ->orWhereHas('billItem.service', fn ($service) => $service->where('name', 'like', "%{$search}%"));
            }))
            ->sum($commissionColumn);

        $filteredBillAmount = $this->commissionQuery()
            ->when($from, fn ($query) => $query->whereDate($dateColumn, '>=', $from))
            ->when($to, fn ($query) => $query->whereDate($dateColumn, '<=', $to))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->whereHas('bill', fn ($bill) => $bill->where('bill_no', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%"))
                    ->orWhereHas('billItem.service', fn ($service) => $service->where('name', 'like', "%{$search}%"));
            }))
            ->sum('bill_items.price');

        return view('staff.results.commission', compact(
            'results',
            'todayCommission',
            'monthCommission',
            'filteredCommission',
            'filteredBillAmount',
            'from',
            'to',
            'staffType',
            'search'
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
        abort_unless($result->billItem?->service?->is_xray, 404);
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
