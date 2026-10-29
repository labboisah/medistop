<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Service;
use App\Models\BillItem;
use App\Models\Bill;
use App\Services\RevenueCalculator;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);
        $search = trim($filters['search'] ?? '');
        $from = $filters['from'] ?? '';
        $to = $filters['to'] ?? '';
        $bills = Bill::withCount('results')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('bill_no', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%")
                    ->orWhereHas('items.service', fn ($service) => $service->where('name', 'like', "%{$search}%"));
            }))
            ->when($from !== '', fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('bills.index', compact('bills', 'search', 'from', 'to'));
    }

    public function requestIndex(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);
        $search = trim($filters['q'] ?? '');
        $dateFrom = $filters['date_from'] ?? '';
        $dateTo = $filters['date_to'] ?? '';

        $items = BillItem::query()
            ->with(['bill.user', 'service', 'result.images'])
            ->whereHas('bill', function ($query) use ($dateFrom, $dateTo) {
                if (auth()->user()->role === 'staff') {
                    $query->where('user_id', auth()->id());
                }
                if ($dateFrom !== '') {
                    $query->whereDate('created_at', '>=', $dateFrom);
                }
                if ($dateTo !== '') {
                    $query->whereDate('created_at', '<=', $dateTo);
                }
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('bill', function ($billQuery) use ($search) {
                        $billQuery->where('bill_no', 'like', "%{$search}%")
                            ->orWhere('patient_name', 'like', "%{$search}%");
                    })->orWhereHas('service', function ($serviceQuery) use ($search) {
                        $serviceQuery->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('investigation-requests.index', compact('items', 'search', 'dateFrom', 'dateTo'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->createForm();
    }

    public function createRequest()
    {
        return $this->createForm(true);
    }

    private function createForm(bool $requestMode = false)
    {
        $serviceOptions = Service::orderBy('name')->get()->map(fn ($service) => [
            'id' => $service->id,
            'name' => $service->name,
            'price' => (float) $service->price,
        ])->values()->all();
        $oldServiceIds = array_map('intval', (array) old('services', []));

        return view('bills.create', compact('serviceOptions', 'oldServiceIds', 'requestMode'));
    }

    public function showRequest(Bill $bill)
    {
        if (auth()->user()->role === 'staff' && $bill->user_id !== auth()->id()) {
            abort(403, 'You can only view investigation requests you registered.');
        }

        $bill->load('items.service', 'user', 'payments.user');

        return view('investigation-requests.show', compact('bill'));
    }

    public function storeRequest(Request $request)
    {
        $request->validate([
            'patient_name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female',
            'less_than_year' => 'nullable|boolean',
            'age_years' => 'nullable|integer|min:0|max:150',
            'age_months' => 'nullable|integer|min:0|max:11',
            'services' => 'required|array|min:1',
            'services.*' => 'exists:services,id',
        ]);

        return $this->store($request, true);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, bool $requestMode = false)
    {
        $request->validate([
            'patient_name' => $requestMode ? 'required|string|max:255' : 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'less_than_year' => 'nullable|boolean',
            'age_years' => 'nullable|integer|min:0|max:150',
            'age_months' => 'nullable|integer|min:0|max:11',
            'discount' => 'nullable|numeric|min:0',
            'services' => 'required|array',
            'services.*' => 'required|integer|distinct|exists:services,id',
        ]);

        $services = Service::with('category.revenueRule')
            ->whereIn('id', $request->services)
            ->get()
            ->keyBy('id');
        $totalAmount = (float) $services->sum('price');
        $discount = (float) ($request->input('discount') ?? 0);

        if (round($discount, 2) > round($totalAmount, 2)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'discount' => 'Discount cannot be greater than the investigations subtotal.',
            ]);
        }

        $billNo = $this->generateBillNo();

        $bill = Bill::create([
            'bill_no' => $billNo,
            'patient_name' => $request->patient_name,
            'user_id' => auth()->id(),
            'gender' => $request->gender,
            'age' => $this->formatPatientAge($request),
        ]);

        foreach ($request->services as $serviceId) {
            $service = $services->get($serviceId);

            $price = $service->price;
            $shares = RevenueCalculator::calculate($service, $price);
            $billItem = BillItem::create([
                'bill_id' => $bill->id,
                'service_id' => $service->id,
                'price' => $price,
            ]);
            $billItem->revenueDistribution()->create([
                'radiologist_amount' => $shares['radiologist'],
                'radiographer_amount' => $shares['radiographer'],
                'staff_amount' => $shares['staff'],
                'annex_amount' => $shares['annex']
            ]);
        }

        $finalAmount = $totalAmount - $discount;

        $bill->update([
            'total_amount' => $totalAmount,
            'discount_amount' => $discount,
            'final_amount' => $finalAmount,
            'total_paid' => 0,
            'balance' => $finalAmount,
            'payment_status' => 'unpaid',
        ]);

        if ($requestMode) {
            return redirect()->route('investigation-requests.show', $bill)
                ->with('success', 'Investigation request registered. The patient can now proceed to the center desk for payment.');
        }

        return redirect()->route('payments.create',$bill)
            ->with('success', 'Bill recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Bill $bill)
    {
        $bill->load('items.service', 'user', 'refunds')->loadCount('results');

        return view('bills.show', compact('bill'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bill $bill)
    {
        if ($bill->results()->exists()) {
            return back()->with('error', 'This bill cannot be deleted because a result has already been entered.');
        }

        $bill->delete();

        return redirect()
            ->route('bills.index')
            ->with('success', 'Bill deleted successfully.');
    }

    private function formatPatientAge(Request $request): ?string
    {
        $years = $request->boolean('less_than_year') ? 0 : (int) ($request->age_years ?? 0);
        $months = (int) ($request->age_months ?? 0);
        $parts = [];

        if ($years > 0) {
            $parts[] = $years.' '.Str::plural('year', $years);
        }

        if ($months > 0) {
            $parts[] = $months.' '.Str::plural('month', $months);
        }

        return $parts ? implode(' ', $parts) : null;
    }

    private function generateBillNo(): string
    {
        do {
            $billNo = 'B' . now()->format('ymd') . '-' . random_int(1000, 9999);
        } while (Bill::where('bill_no', $billNo)->exists());

        return $billNo;
    }
}
