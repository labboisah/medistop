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
    public function index()
    {
        $bills = \App\Models\Bill::withCount('results')->latest()->paginate(15);

        return view('bills.index', compact('bills'));
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
            'services' => 'required|array',
            'services.*' => 'exists:services,id',
        ]);

        $billNo = $this->generateBillNo();

        $totalAmount = 0;
        $totalStaffShare = 0;
        $totalAnnexShare = 0;

        $bill = Bill::create([
            'bill_no' => $billNo,
            'patient_name' => $request->patient_name,
            'user_id' => auth()->id(),
            'gender' => $request->gender,
            'age' => $this->formatPatientAge($request),
        ]);

        foreach ($request->services as $serviceId) {

            $service = \App\Models\Service::find($serviceId);

            $price = $service->price;
            $rule = $service->category->revenueRule;
            $staffShare = $price * ($rule->staff_percent / 100);
            $annexShare = $price * ($rule->annex_percent / 100);
            $shares = RevenueCalculator::calculate($service, $price);
            $billItem = BillItem::create([
                'bill_id' => $bill->id,
                'service_id' => $service->id,
                'price' => $price,
            ]);

            $totalAmount += $price;
            $totalStaffShare += $staffShare;
            $totalAnnexShare += $annexShare;

            $billItem->revenueDistribution()->create([
                'radiologist_amount' => $shares['radiologist'],
                'radiographer_amount' => $shares['radiographer'],
                'staff_amount' => $shares['staff'],
                'annex_amount' => $shares['annex']
            ]);
        }

        $discount = $request->discount ?? 0;
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
