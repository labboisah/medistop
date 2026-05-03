<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillRefund;
use Illuminate\Http\Request;

class BillRefundController extends Controller
{
    private function reasons(): array
    {
        return [
            'Overpayment',
            'Service not rendered',
            'Pricing error',
            'Patient request',
            'Other',
        ];
    }

    public function index()
    {
        $refunds = BillRefund::with('bill')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('refunds.index', compact('refunds'));
    }

    public function create(Request $request)
    {
        $bill = null;

        if ($request->filled('bill_no')) {
            $bill = Bill::where('bill_no', $request->bill_no)
                ->where('user_id', auth()->id())
                ->with('refunds')
                ->first();
        }

        return view('refunds.create', [
            'bill' => $bill,
            'reasons' => $this->reasons(),
            'billNo' => $request->bill_no,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'bill_no' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'reason' => ['required', 'string', 'in:' . implode(',', $this->reasons())],
        ]);

        $bill = Bill::where('bill_no', $request->bill_no)
            ->where('user_id', auth()->id())
            ->with('refunds')
            ->first();

        if (! $bill) {
            return back()
                ->withInput()
                ->withErrors(['bill_no' => 'Bill not found.']);
        }

        $paid = $bill->total_paid ?? 0;
        $refunded = $bill->refunds->sum('amount');
        $available = max(0, $paid - $refunded);

        if ($available <= 0 || $request->amount > $available) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'Refund cannot exceed available settled amount.']);
        }

        $bill->refunds()->create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'reason' => $request->reason,
        ]);

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Refund recorded successfully.');
    }

    public function edit(BillRefund $refund)
    {
        if ($refund->user_id !== auth()->id()) {
            abort(403);
        }

        return view('refunds.edit', [
            'refund' => $refund,
            'reasons' => $this->reasons(),
        ]);
    }

    public function update(Request $request, BillRefund $refund)
    {
        if ($refund->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => ['required', 'string', 'in:' . implode(',', $this->reasons())],
        ]);

        $bill = $refund->bill;
        $paid = $bill->total_paid ?? 0;
        $previousRefunds = $bill->refunds()->sum('amount') - $refund->amount;
        $available = max(0, $paid - $previousRefunds);

        if ($request->amount > $available) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'Refund cannot exceed available settled amount.']);
        }

        $refund->update($request->only('amount', 'reason'));

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Refund updated successfully.');
    }

    public function destroy(BillRefund $refund)
    {
        if ($refund->user_id !== auth()->id()) {
            abort(403);
        }

        $bill = $refund->bill;
        $refund->delete();

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Refund deleted successfully.');
    }
}
