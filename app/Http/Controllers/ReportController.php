<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Expense;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Report;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use App\Exports\FinancialReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function generate(Request $request)
    {
        if ($request->today) {
            $from = now()->startOfDay();
            $to = now()->endOfDay();
        } else {
            $from = $request->from;
            $to = $request->to;
        }

        $bills = Bill::whereBetween('created_at', [$from,$to])->get();
        $expenses = Expense::whereBetween('expense_date', [$from,$to])->get();

        $gross = $bills->sum('total_amount');
        $discount = $bills->sum('discount_amount');
        $net = $gross - $discount;

        $staffShare = $net * 0.4;
        $annexShare = $net * 0.6;

        $totalExpense = $expenses->sum('amount');
        $profit = $annexShare - $totalExpense;

        $reporterName = auth()->user()->name;
        $reporterEmail = auth()->user()->email;
        $generatedDate = now()->format('d M Y');
        $generatedTime = now()->format('h:i A');
        $reportId = 'ANNEX-' . now()->format('YmdHis');

        $data = compact(
            'bills','expenses',
            'gross','discount','net',
            'staffShare','annexShare',
            'totalExpense','profit',
            'from','to',
            'reporterName',
            'reporterEmail',
            'generatedDate',
            'generatedTime',
            'reportId'
        );

        if ($request->type == 'summary') {
            return view('admin.reports.summary', $data);
        }

        return view('view.reports.detailed', $data);
    }

    public function exportCsv(Request $request)
    {
        $filename = "financial-report.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function() use ($request) {

            $file = fopen('php://output','w');

            fputcsv($file, ['Gross','Discount','Staff Share','Annex Share','Expenses','Profit']);

            // calculate same logic again or reuse helper
            // (simplified example)
            fputcsv($file, [100000,5000,38000,57000,20000,37000]);

            fclose($file);
        };

        return response()->stream($callback,200,$headers);
    }

    public function exportExcel(Request $request)
    {
        $from = $request->from;
        $to = $request->to;
        $reportId = 'ANNEX-' . now()->format('YmdHis');

        return Excel::download(
            new FinancialReportExport($from, $to, $reportId),
            "financial-report-$reportId.xlsx",
            \Maatwebsite\Excel\Excel::XLSX,
            ['charts' => true]
        );
    }

   

    public function exportPdf(Request $request)
    {
        $from = $request->from;
        $to = $request->to;

        $bills = Bill::whereBetween('created_at', [$from,$to])->get();
        $expenses = Expense::whereBetween('expense_date', [$from,$to])->get();

        $gross = $bills->sum('total_amount');
        $discount = $bills->sum('discount_amount');
        $net = $gross - $discount;

        $staffShare = $net * 0.4;
        $annexShare = $net * 0.6;
        $totalExpense = $expenses->sum('amount');
        $profit = $annexShare - $totalExpense;

        $reportId = 'ANNEX-' . now()->format('YmdHis');
        $reporterName = auth()->user()->name;
        $reporterEmail = auth()->user()->email;
        $generatedDate = now()->format('d M Y');
        $generatedTime = now()->format('h:i A');
        $reportId = 'ANNEX-' . now()->format('YmdHis');
        $generatedReport = Report::create([
            'report_reference' => $reportId,
            'from_date' => $from,
            'to_date' => $to,
            'gross' => $gross,
            'discount' => $discount,
            'net' => $net,
            'staff_share' => $staffShare,
            'annex_share' => $annexShare,
            'expenses' => $totalExpense,
            'profit' => $profit,
            'user_id' => auth()->id(),
        ]);

       

        $chartImage = $request->chart_image;
        $logoPath = public_path('images/logo.png');

        $pdf = Pdf::loadView('admin.reports.pdf', compact(
            'gross','discount','net',
            'staffShare','annexShare',
            'totalExpense','profit',
            'from','to',
            'chartImage',
            'reportId',
            'logoPath',
            'reporterName',
            'reporterEmail',
            'generatedDate',
            'generatedTime',
            'reportId'
        ))->setPaper('a4', 'portrait');

        $fileName = "reports/$reportId.pdf";
        Storage::put($fileName, $pdf->output());

        $generatedReport->update([
            'pdf_path' => $fileName
        ]);

        return $pdf->download($reportId.'.pdf');
    }
}