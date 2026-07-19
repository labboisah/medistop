<?php

namespace App\Http\Controllers;

use App\Models\BillResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminStaffReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $staffId] = $this->filters($request);

        $staffMembers = User::where('role', 'staff')->orderBy('name')->get();
        $results = $this->baseQuery($from, $to, $staffId)
            ->latest('completed_at')
            ->paginate(20)
            ->withQueryString();

        $results->getCollection()->transform(function (BillResult $result) {
            $result->bill_amount = $this->billAmountForResult($result);
            $result->commission_amount = $this->commissionForResult($result);
            return $result;
        });

        $summary = $this->buildSummary($from, $to, $staffId);
        $totalResults = $summary->sum('total_results');
        $totalBillAmount = $summary->sum('total_bill_amount');
        $totalCommission = $summary->sum('total_commission');

        return view('admin.staff-reports.index', compact(
            'staffMembers',
            'results',
            'summary',
            'totalResults',
            'totalBillAmount',
            'totalCommission',
            'from',
            'to',
            'staffId'
        ));
    }

    public function download(Request $request)
    {
        [$from, $to, $staffId] = $this->filters($request);
        $summary = $this->buildSummary($from, $to, $staffId);
        $filename = 'staff-work-summary-' . now()->format('YmdHis') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($summary, $from, $to) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Staff Work Summary']);
            fputcsv($file, ['From', $from ?: 'All time', 'To', $to ?: 'All time']);
            fputcsv($file, []);
            fputcsv($file, ['Performed By', 'Staff Type', 'Total Results', 'Total Bill Amount', 'Total Commission']);

            foreach ($summary as $row) {
                fputcsv($file, [
                    $row['staff_name'],
                    $row['staff_type'],
                    $row['total_results'],
                    $row['total_bill_amount'],
                    $row['total_commission'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function baseQuery(?string $from, ?string $to, ?string $staffId)
    {
        return BillResult::with([
                'bill',
                'billItem.service',
                'billItem.revenueDistribution',
                'staff',
            ])
            ->when($from, fn ($query) => $query->whereDate('completed_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('completed_at', '<=', $to))
            ->when($staffId, fn ($query) => $query->where('staff_id', $staffId));
    }

    private function buildSummary(?string $from, ?string $to, ?string $staffId)
    {
        return $this->baseQuery($from, $to, $staffId)
            ->get()
            ->groupBy('staff_id')
            ->map(function ($results) {
                $staff = $results->first()->staff;

                return [
                    'staff_id' => $staff?->id,
                    'staff_name' => $staff?->name ?? 'Unknown',
                    'staff_type' => strtoupper($staff?->staff_type ?? 'staff'),
                    'total_results' => $results->count(),
                    'total_bill_amount' => $results->sum(fn ($result) => $this->billAmountForResult($result)),
                    'total_commission' => $results->sum(fn ($result) => $this->commissionForResult($result)),
                ];
            })
            ->sortBy('staff_name')
            ->values();
    }

    private function commissionForResult(BillResult $result): float
    {
        $distribution = $result->billItem?->revenueDistribution;

        if (! $distribution) {
            return 0;
        }

        return (float) match ($result->staff?->staff_type ?? 'staff') {
            'radiologist' => $distribution->radiologist_amount,
            'radiographer' => $distribution->radiographer_amount,
            default => $distribution->staff_amount,
        };
    }

    private function billAmountForResult(BillResult $result): float
    {
        return (float) ($result->billItem?->price ?? 0);
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'staff_id' => 'nullable|exists:users,id',
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
            $validated['staff_id'] ?? null,
        ];
    }
}
