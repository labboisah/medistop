<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillResult;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminStaffReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $staffId] = $this->filters($request);

        $staffMembers = User::where('role', 'staff')->orderBy('name')->get();
        $results = $this->baseQuery($from, $to, $staffId)
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        $results->getCollection()->transform(function (BillResult $result) {
            $result->bill_amount = $this->billAmountForResult($result);
            $result->commission_amount = $this->commissionForDetailedResult($result);
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

    public function performance(Request $request)
    {
        [$from, $to, $userId] = $this->performanceFilters($request);
        $users = User::orderBy('name')->get();
        $audit = $this->buildPerformanceAudit($from, $to, $userId);

        return view('admin.staff-reports.performance', [
            'users' => $users,
            'from' => $from,
            'to' => $to,
            'userId' => $userId,
            'rows' => $audit['rows'],
            'userSummary' => $audit['userSummary'],
            'chartLabels' => $audit['chartLabels'],
            'chartNetRevenue' => $audit['chartNetRevenue'],
            'chartPayments' => $audit['chartPayments'],
            'totalBills' => $audit['totalBills'],
            'totalGross' => $audit['totalGross'],
            'totalDiscount' => $audit['totalDiscount'],
            'totalNet' => $audit['totalNet'],
            'totalPayments' => $audit['totalPayments'],
        ]);
    }

    public function downloadPerformance(Request $request)
    {
        [$from, $to, $userId] = $this->performanceFilters($request);
        $audit = $this->buildPerformanceAudit($from, $to, $userId);
        $filename = 'staff-performance-audit-' . now()->format('YmdHis') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($audit, $from, $to) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Staff Performance Audit']);
            fputcsv($file, ['From', $from, 'To', $to]);
            fputcsv($file, []);
            fputcsv($file, ['Date', 'User', 'Bills', 'Gross', 'Discount', 'Net Revenue', 'Payments Collected']);

            foreach ($audit['rows'] as $row) {
                fputcsv($file, [
                    $row['date'],
                    $row['user_name'],
                    $row['bill_count'],
                    $row['gross'],
                    $row['discount'],
                    $row['net'],
                    $row['payments'],
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
                'performer',
                'reporter',
            ])
            ->when($from, function ($query) use ($from) {
                $query->where(function ($dateQuery) use ($from) {
                    $dateQuery->whereDate('performed_at', '>=', $from)
                        ->orWhereDate('reported_at', '>=', $from)
                        ->orWhereDate('completed_at', '>=', $from);
                });
            })
            ->when($to, function ($query) use ($to) {
                $query->where(function ($dateQuery) use ($to) {
                    $dateQuery->whereDate('performed_at', '<=', $to)
                        ->orWhereDate('reported_at', '<=', $to)
                        ->orWhereDate('completed_at', '<=', $to);
                });
            })
            ->when($staffId, function ($query) use ($staffId) {
                $query->where(function ($staffQuery) use ($staffId) {
                    $staffQuery->where('performed_by', $staffId)
                        ->orWhere('reported_by', $staffId)
                        ->orWhere('staff_id', $staffId);
                });
            });
    }

    private function buildSummary(?string $from, ?string $to, ?string $staffId)
    {
        return $this->baseQuery($from, $to, $staffId)
            ->get()
            ->flatMap(function (BillResult $result) {
                $rows = collect();

                if ($result->performer) {
                    $rows->push($this->summaryRow($result, $result->performer, 'radiographer'));
                }

                $reporter = $result->reporter ?? $result->staff;
                if ($reporter) {
                    $rows->push($this->summaryRow($result, $reporter, $reporter->staff_type ?? 'staff'));
                }

                return $rows;
            })
            ->groupBy('staff_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'staff_id' => $first['staff_id'],
                    'staff_name' => $first['staff_name'],
                    'staff_type' => $first['staff_type'],
                    'total_results' => $rows->count(),
                    'total_bill_amount' => $rows->sum('bill_amount'),
                    'total_commission' => $rows->sum('commission_amount'),
                ];
            })
            ->sortBy('staff_name')
            ->values();
    }

    private function summaryRow(BillResult $result, User $staff, string $staffType): array
    {
        return [
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'staff_type' => strtoupper($staffType),
            'bill_amount' => $this->billAmountForResult($result),
            'commission_amount' => $this->commissionForStaffType($result, $staffType),
        ];
    }

    private function commissionForDetailedResult(BillResult $result): float
    {
        if ($result->performer && $result->reporter && $result->performer->id !== $result->reporter->id) {
            return $this->commissionForStaffType($result, 'radiographer')
                + $this->commissionForStaffType($result, $result->reporter->staff_type ?? 'staff');
        }

        $staff = $result->reporter ?? $result->performer ?? $result->staff;

        return $this->commissionForStaffType($result, $staff?->staff_type ?? 'staff');
    }

    private function commissionForStaffType(BillResult $result, string $staffType): float
    {
        $distribution = $result->billItem?->revenueDistribution;

        if (! $distribution) {
            return 0;
        }

        return (float) match ($staffType) {
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

    private function performanceFilters(Request $request): array
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $from = $validated['from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? now()->toDateString();

        if ($to < $from) {
            throw ValidationException::withMessages([
                'to' => 'The to date must be after or equal to the from date.',
            ]);
        }

        return [$from, $to, $validated['user_id'] ?? null];
    }

    private function buildPerformanceAudit(string $from, string $to, ?string $userId): array
    {
        $bills = Bill::with('user')
            ->selectRaw('DATE(created_at) as report_date, user_id, COUNT(*) as bill_count, SUM(total_amount) as gross, SUM(discount_amount) as discount, SUM(final_amount) as net')
            ->whereBetween('created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ])
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->groupBy('report_date', 'user_id')
            ->get();

        $payments = Payment::with('user')
            ->selectRaw('DATE(created_at) as report_date, user_id, SUM(amount) as payments')
            ->whereBetween('created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ])
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->groupBy('report_date', 'user_id')
            ->get();

        $users = User::whereIn('id', $bills->pluck('user_id')->merge($payments->pluck('user_id'))->unique())
            ->get()
            ->keyBy('id');

        if ($userId && ! $users->has((int) $userId)) {
            $selectedUser = User::find($userId);

            if ($selectedUser) {
                $users->put($selectedUser->id, $selectedUser);
            }
        }

        $billMap = $bills->keyBy(fn ($bill) => $bill->report_date.'-'.$bill->user_id);
        $paymentMap = $payments->keyBy(fn ($payment) => $payment->report_date.'-'.$payment->user_id);
        $activeUserIds = $userId ? collect([(int) $userId]) : $users->keys();
        $rows = collect();

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $dateString = $date->toDateString();

            foreach ($activeUserIds as $activeUserId) {
                $key = $dateString.'-'.$activeUserId;
                $bill = $billMap->get($key);
                $payment = $paymentMap->get($key);

                if (! $bill && ! $payment) {
                    continue;
                }

                $rows->push([
                    'date' => $dateString,
                    'date_label' => $date->format('d M Y'),
                    'user_id' => $activeUserId,
                    'user_name' => optional($users->get($activeUserId))->name ?? 'Unknown',
                    'bill_count' => (int) ($bill->bill_count ?? 0),
                    'gross' => (float) ($bill->gross ?? 0),
                    'discount' => (float) ($bill->discount ?? 0),
                    'net' => (float) ($bill->net ?? 0),
                    'payments' => (float) ($payment->payments ?? 0),
                ]);
            }
        }

        $dailyTotals = $rows->groupBy('date')->map(fn ($dateRows) => [
            'net' => $dateRows->sum('net'),
            'payments' => $dateRows->sum('payments'),
        ]);

        $chartLabels = [];
        $chartNetRevenue = [];
        $chartPayments = [];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $dateString = $date->toDateString();
            $chartLabels[] = $date->format('d M');
            $chartNetRevenue[] = round($dailyTotals[$dateString]['net'] ?? 0, 2);
            $chartPayments[] = round($dailyTotals[$dateString]['payments'] ?? 0, 2);
        }

        $userSummary = $rows->groupBy('user_id')
            ->map(function ($userRows) {
                $first = $userRows->first();

                return [
                    'user_name' => $first['user_name'],
                    'bill_count' => $userRows->sum('bill_count'),
                    'gross' => $userRows->sum('gross'),
                    'discount' => $userRows->sum('discount'),
                    'net' => $userRows->sum('net'),
                    'payments' => $userRows->sum('payments'),
                ];
            })
            ->sortBy('user_name')
            ->values();

        return [
            'rows' => $rows->sortByDesc('date')->values(),
            'userSummary' => $userSummary,
            'chartLabels' => $chartLabels,
            'chartNetRevenue' => $chartNetRevenue,
            'chartPayments' => $chartPayments,
            'totalBills' => $rows->sum('bill_count'),
            'totalGross' => $rows->sum('gross'),
            'totalDiscount' => $rows->sum('discount'),
            'totalNet' => $rows->sum('net'),
            'totalPayments' => $rows->sum('payments'),
        ];
    }
}
