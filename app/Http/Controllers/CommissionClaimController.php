<?php

namespace App\Http\Controllers;

use App\Models\BillResult;
use App\Models\CommissionClaim;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CommissionClaimController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'staff_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $staff = $request->user();
        abort_unless(in_array($staff->staff_type, ['radiologist', 'radiographer'], true), 403);

        $approver = User::query()->join('bills', 'bills.user_id', '=', 'users.id')
            ->where('users.role', 'user')
            ->whereBetween('bills.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->select('users.*')->selectRaw('SUM(bills.final_amount) as month_revenue')
            ->groupBy('users.id')->orderByDesc('month_revenue')->orderBy('users.id')->first();
        if (! $approver) {
            throw ValidationException::withMessages(['amount' => 'No revenue owner is available to receive this claim right now.']);
        }

        DB::transaction(function () use ($staff, $approver, $data) {
            User::whereKey($staff->id)->lockForUpdate()->firstOrFail();
            $available = $this->earnedCommission($staff);
            $reserved = CommissionClaim::where('staff_id', $staff->id)
                ->whereIn('status', ['pending', 'settled'])
                ->sum(DB::raw("CASE WHEN status = 'pending' THEN requested_amount ELSE settled_amount END"));
            $claimAmount = round($available - (float) $reserved, 2);
            if ($claimAmount <= 0) {
                throw ValidationException::withMessages(['amount' => 'There is no available commission to claim.']);
            }

            CommissionClaim::create([
                'staff_id' => $staff->id,
                'approver_id' => $approver->id,
                'requested_amount' => $claimAmount,
                'staff_note' => $data['staff_note'] ?? null,
            ]);
        });

        return back()->with('success', 'Commission claim sent to '.$approver->name.'.');
    }

    public function index(Request $request): View
    {
        $claims = CommissionClaim::with('staff')->where('approver_id', $request->user()->id)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()->paginate(20)->withQueryString();
        return view('commission-claims.index', compact('claims'));
    }

    public function settle(Request $request, CommissionClaim $claim): RedirectResponse
    {
        abort_unless((int) $claim->approver_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'settled_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'settlement_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $claim, $data) {
            $locked = CommissionClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['settled_amount' => 'This claim has already been processed.']);
            }
            if ((float) $data['settled_amount'] > (float) $locked->requested_amount) {
                throw ValidationException::withMessages(['settled_amount' => 'Settlement cannot exceed the requested amount.']);
            }
            $locked->update([
                'status' => 'settled', 'settled_amount' => $data['settled_amount'],
                'settled_by' => $request->user()->id, 'settled_at' => now(),
                'settlement_note' => $data['settlement_note'] ?? null,
            ]);
        });
        return back()->with('success', 'Claim settled. The actual settled amount will be deducted from the staff commission.');
    }

    public function reject(Request $request, CommissionClaim $claim): RedirectResponse
    {
        abort_unless((int) $claim->approver_id === (int) $request->user()->id, 403);
        abort_unless($claim->status === 'pending', 422);
        $claim->update(['status' => 'rejected', 'settled_by' => $request->user()->id, 'settled_at' => now()]);
        return back()->with('success', 'Claim rejected.');
    }

    private function earnedCommission(User $staff): float
    {
        $column = $staff->staff_type === 'radiologist' ? 'radiologist_amount' : 'radiographer_amount';
        $query = BillResult::query()->join('bill_items', 'bill_results.bill_item_id', '=', 'bill_items.id')
            ->leftJoin('revenue_distributions', 'bill_items.id', '=', 'revenue_distributions.bill_item_id')
            ->whereNotNull($staff->staff_type === 'radiologist' ? 'bill_results.reported_at' : 'bill_results.performed_at');
        if ($staff->staff_type === 'radiologist') {
            $query->where(fn ($q) => $q->where('bill_results.reported_by', $staff->id)
                ->orWhere(fn ($f) => $f->whereNull('bill_results.reported_by')->where('bill_results.staff_id', $staff->id)));
        } else {
            $query->where('bill_results.performed_by', $staff->id);
        }
        return (float) $query->sum('revenue_distributions.'.$column);
    }
}
