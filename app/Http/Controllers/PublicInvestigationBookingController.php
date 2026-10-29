<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\InvestigationBooking;
use App\Models\Service;
use App\Services\RevenueCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicInvestigationBookingController extends Controller
{
    public function create()
    {
        $services = Service::orderBy('name')->get();
        $serviceOptions = $services->map(fn ($service) => [
            'id' => $service->id,
            'name' => $service->name,
            'price' => (float) $service->price,
        ])->values()->all();
        $oldServiceIds = array_map('intval', (array) old('services', []));

        return view('public-bookings.create', compact('serviceOptions', 'oldServiceIds'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'gender' => 'nullable|in:male,female',
            'less_than_year' => 'nullable|boolean',
            'age_years' => 'nullable|integer|min:0|max:150',
            'age_months' => 'nullable|integer|min:0|max:11',
            'services' => 'required|array|min:1',
            'services.*' => 'required|integer|distinct|exists:services,id',
        ]);

        $booking = DB::transaction(function () use ($data, $request) {
            $reference = Str::upper(Str::random(10));
            while (InvestigationBooking::where('reference', $reference)->exists()) {
                $reference = Str::upper(Str::random(10));
            }

            $booking = InvestigationBooking::create([
                'reference' => $reference,
                'patient_name' => $data['patient_name'],
                'phone' => $data['phone'],
                'gender' => $data['gender'] ?? null,
                'age' => $this->formatPatientAge($request),
                'status' => 'pending',
            ]);

            $services = Service::whereIn('id', $data['services'])->get()->keyBy('id');
            foreach ($data['services'] as $serviceId) {
                $service = $services->get($serviceId);
                $booking->items()->create([
                    'service_id' => $service->id,
                    'price' => $service->price,
                ]);
            }

            return $booking;
        });

        return redirect()->route('public.investigation-bookings.confirmation', $booking->reference);
    }

    public function confirmation(string $reference)
    {
        $booking = InvestigationBooking::with('items.service')
            ->where('reference', $reference)
            ->firstOrFail();

        return view('public-bookings.confirmation', compact('booking'));
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $search = trim($filters['search'] ?? '');
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        if ($from && $to && $to < $from) {
            return back()->withErrors(['to' => 'The To date must be on or after the From date.'])->withInput();
        }

        $applyDateFilters = static function ($query) use ($from, $to) {
            if ($from) {
                $query->whereDate('created_at', '>=', $from);
            }
            if ($to) {
                $query->whereDate('created_at', '<=', $to);
            }

            return $query;
        };

        $bookings = $applyDateFilters(InvestigationBooking::query())
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('reference', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('items.service', fn ($service) => $service->where('name', 'like', "%{$search}%"));
            }))
            ->with('items.service', 'acceptedBy')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statsQuery = $applyDateFilters(InvestigationBooking::query());
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'accepted' => (clone $statsQuery)->where('status', 'accepted')->count(),
        ];

        return view('investigation-bookings.index', compact('bookings', 'stats', 'from', 'to', 'search'));
    }

    public function show(InvestigationBooking $booking)
    {
        $booking->load('items.service', 'bill');

        return view('investigation-bookings.show', compact('booking'));
    }

    public function accept(InvestigationBooking $booking)
    {
        $bill = DB::transaction(function () use ($booking) {
            $booking = InvestigationBooking::with('items.service.category.revenueRule')
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if ($booking->bill_id) {
                return Bill::findOrFail($booking->bill_id);
            }

            abort_unless($booking->status === 'pending', 409, 'This booking is no longer pending.');

            $bill = Bill::create([
                'bill_no' => $this->generateBillNo(),
                'patient_name' => $booking->patient_name,
                'gender' => $booking->gender,
                'age' => $booking->age,
                'user_id' => auth()->id(),
            ]);

            $total = 0;
            foreach ($booking->items as $bookingItem) {
                $service = $bookingItem->service;
                $price = (float) $bookingItem->price;
                $item = $bill->items()->create([
                    'service_id' => $service->id,
                    'price' => $price,
                ]);
                $shares = RevenueCalculator::calculate($service, $price);
                $item->revenueDistribution()->create([
                    'radiologist_amount' => $shares['radiologist'],
                    'radiographer_amount' => $shares['radiographer'],
                    'staff_amount' => $shares['staff'],
                    'annex_amount' => $shares['annex'],
                ]);
                $total += $price;
            }

            $bill->update([
                'total_amount' => $total,
                'discount_amount' => 0,
                'final_amount' => $total,
                'total_paid' => 0,
                'balance' => $total,
                'payment_status' => 'unpaid',
            ]);

            $booking->update([
                'status' => 'accepted',
                'bill_id' => $bill->id,
                'accepted_by' => auth()->id(),
            ]);

            return $bill;
        });

        return redirect()->route('payments.create', $bill)
            ->with('success', 'Booking accepted. Confirm the payment received to issue the receipt.');
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
            $billNo = 'B'.now()->format('ymd').'-'.random_int(1000, 9999);
        } while (Bill::where('bill_no', $billNo)->exists());

        return $billNo;
    }
}
