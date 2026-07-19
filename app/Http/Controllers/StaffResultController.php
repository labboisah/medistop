<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillResult;
use App\Models\ResultTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffResultController extends Controller
{
    public function dashboard()
    {
        $todayResults = BillResult::where('staff_id', auth()->id())
            ->whereDate('completed_at', today())
            ->count();

        $monthResults = BillResult::where('staff_id', auth()->id())
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->count();

        $monthCommission = $this->commissionQuery()
            ->whereMonth('bill_results.completed_at', now()->month)
            ->whereYear('bill_results.completed_at', now()->year)
            ->sum($this->commissionColumn());

        $recentResults = BillResult::with('bill', 'billItem.service')
            ->where('staff_id', auth()->id())
            ->latest('completed_at')
            ->limit(5)
            ->get();

        return view('staff.dashboard', compact(
            'todayResults',
            'monthResults',
            'monthCommission',
            'recentResults'
        ));
    }

    public function index()
    {
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
            'items.result.staff',
            'user',
            'payments.user',
        ]);

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

        return view('staff.results.entry', compact('bill', 'templates', 'templatePayload'));
    }

    public function store(Request $request, Bill $bill)
    {
        $this->ensurePaidBill($bill);

        $validated = $request->validate([
            'bill_item_id' => 'required|exists:bill_items,id',
            'clinical_note' => 'nullable|string',
            'findings' => 'required|string',
            'impression' => 'nullable|string',
            'save_template' => 'nullable|boolean',
            'template_name' => 'required_if:save_template,1|nullable|string|max:255',
        ]);

        $billItem = $bill->items()->whereKey($validated['bill_item_id'])->firstOrFail();

        $existingResult = BillResult::where('bill_item_id', $billItem->id)->first();
        if ($existingResult && $existingResult->staff_id !== auth()->id()) {
            abort(403, 'This investigation already has a result entered by another staff member.');
        }

        $result = BillResult::updateOrCreate(
            ['bill_item_id' => $billItem->id],
            [
                'bill_id' => $bill->id,
                'staff_id' => auth()->id(),
                'clinical_note' => $validated['clinical_note'] ?? null,
                'findings' => $validated['findings'],
                'impression' => $validated['impression'] ?? null,
                'status' => 'completed',
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

        return redirect()
            ->route('staff.results.print', $result)
            ->with('success', 'Result saved successfully.');
    }

    public function reports(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $results = BillResult::with('bill', 'billItem.service', 'staff')
            ->where('staff_id', auth()->id())
            ->when($from, fn ($query) => $query->whereDate('completed_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('completed_at', '<=', $to))
            ->latest('completed_at')
            ->paginate(15)
            ->withQueryString();

        return view('staff.results.reports', compact('results', 'from', 'to'));
    }

    public function commission(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $commissionColumn = $this->commissionColumn();
        $staffType = auth()->user()->staff_type ?? 'staff';

        $results = $this->commissionQuery()
            ->select([
                'bill_results.*',
                'bill_items.price as bill_amount',
                $commissionColumn . ' as commission_amount',
            ])
            ->when($from, fn ($query) => $query->whereDate('bill_results.completed_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('bill_results.completed_at', '<=', $to))
            ->with('bill', 'billItem.service')
            ->latest('bill_results.completed_at')
            ->paginate(15)
            ->withQueryString();

        $todayCommission = $this->commissionQuery()
            ->whereDate('bill_results.completed_at', today())
            ->sum($commissionColumn);

        $monthCommission = $this->commissionQuery()
            ->whereMonth('bill_results.completed_at', now()->month)
            ->whereYear('bill_results.completed_at', now()->year)
            ->sum($commissionColumn);

        $filteredCommission = $this->commissionQuery()
            ->when($from, fn ($query) => $query->whereDate('bill_results.completed_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('bill_results.completed_at', '<=', $to))
            ->sum($commissionColumn);

        $filteredBillAmount = $this->commissionQuery()
            ->when($from, fn ($query) => $query->whereDate('bill_results.completed_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('bill_results.completed_at', '<=', $to))
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
        if ($result->staff_id !== auth()->id()) {
            abort(403);
        }

        $result->load([
            'bill.user',
            'bill.payments.user',
            'billItem.service.category',
            'staff',
        ]);

        return view('staff.results.print', compact('result'));
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
        return BillResult::query()
            ->where('bill_results.staff_id', auth()->id())
            ->join('bill_items', 'bill_results.bill_item_id', '=', 'bill_items.id')
            ->leftJoin('revenue_distributions', 'bill_items.id', '=', 'revenue_distributions.bill_item_id');
    }

    private function commissionColumn(): string
    {
        return match (auth()->user()->staff_type ?? 'staff') {
            'radiologist' => 'revenue_distributions.radiologist_amount',
            'radiographer' => 'revenue_distributions.radiographer_amount',
            default => 'revenue_distributions.staff_amount',
        };
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
