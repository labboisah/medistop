<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BillRefundController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\StaffResultController;
use App\Http\Controllers\AdminStaffReportController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\LocalServerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'showServerControl' => LocalServerController::shouldShowControl(request()),
    ]);
});

Route::get('/server-control/status', [LocalServerController::class, 'status'])->name('server-control.status');
Route::post('/server-control/connect', [LocalServerController::class, 'connect'])->name('server-control.connect');
Route::post('/server-control/disconnect', [LocalServerController::class, 'disconnect'])->name('server-control.disconnect');

Route::get('/verify-report/{reference}', function ($reference) {

    $report = \App\Models\Report::where('report_reference',$reference)->firstOrFail();

    return view('reports.verify', compact('report'));
});

Route::get('/dashboard', function () {
    if (auth()->user()->role === 'staff') {
        return redirect()->route('staff.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    Route::resource('categories', CategoryController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('users', UserController::class);
    Route::resource('finances', FinanceController::class);
    Route::get('expenses/report', [ExpenseController::class, 'adminReport'])->name('expenses.report');
    Route::get('expenses/download-csv', [ExpenseController::class, 'adminDownloadCsv'])->name('expenses.download-csv');
    Route::get('expenses', [ExpenseController::class, 'adminIndex'])->name('expenses.index');
    Route::get('expenses/create', [ExpenseController::class, 'adminCreate'])->name('expenses.create');
    Route::post('expenses', [ExpenseController::class, 'adminStore'])->name('expenses.store');
    Route::resource('salaries', SalaryController::class)->only(['index', 'store', 'destroy']);
    Route::get('staff-reports', [AdminStaffReportController::class, 'index'])->name('staff-reports.index');
    Route::get('staff-reports/download', [AdminStaffReportController::class, 'download'])->name('staff-reports.download');
    Route::get('staff-performance', [AdminStaffReportController::class, 'performance'])->name('staff-performance.index');
    Route::get('staff-performance/download', [AdminStaffReportController::class, 'downloadPerformance'])->name('staff-performance.download');
    Route::post('database/backup', [DatabaseBackupController::class, 'backup'])->name('database.backup');

    Route::prefix('reports')
        ->name('reports.')
        ->group(function () {

            Route::get('/', [ReportController::class,'index'])->name('index');
            Route::post('/generate', [ReportController::class,'generate'])->name('generate');

            Route::post('/pdf', [ReportController::class,'exportPdf'])->name('pdf');
            Route::get('/csv', [ReportController::class,'exportCsv'])->name('csv');
            Route::post('/excel', [ReportController::class,'exportExcel'])->name('excel');
    });
        
});

Route::middleware(['auth', 'user'])->group(function () {

    Route::post('/pdf', [ReportController::class,'exportPdf'])->name('reports.pdf');

    Route::get('refunds', [BillRefundController::class, 'index'])->name('refunds.index');
    Route::get('refunds/create', [BillRefundController::class, 'create'])->name('refunds.create');
    Route::post('refunds', [BillRefundController::class, 'store'])->name('refunds.store');
    Route::get('refunds/{refund}/edit', [BillRefundController::class, 'edit'])->name('refunds.edit');
    Route::put('refunds/{refund}', [BillRefundController::class, 'update'])->name('refunds.update');
    Route::delete('refunds/{refund}', [BillRefundController::class, 'destroy'])->name('refunds.destroy');

    Route::resource('bills', BillController::class);
    Route::resource('expenses', ExpenseController::class);

    Route::prefix('payments')
    ->name('payments.')
    ->group(function () {

        // Payment ledger (all payments)
        Route::get('/', 
            [PaymentController::class, 'index'])
            ->name('index');

        // Create payment for a specific bill
        Route::get('/bill/{bill}', 
            [PaymentController::class, 'create'])
            ->name('create');

        // Store payment
        Route::post('/', 
            [PaymentController::class, 'store'])
            ->name('store');

        // Edit payment
        Route::get('/{payment}/edit', 
            [PaymentController::class, 'edit'])
            ->name('edit');

        // Update payment
        Route::put('/{payment}', 
            [PaymentController::class, 'update'])
            ->name('update');

        // Delete payment (recommended)
        Route::delete('/{payment}', 
            [PaymentController::class, 'destroy'])
            ->name('destroy');

        Route::get('{bill}/receipt/{type?}', 
            [PaymentController::class, 'receipt'])
            ->name('receipt');    

    });

});

Route::middleware(['auth', 'staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/dashboard', [StaffResultController::class, 'dashboard'])->name('dashboard');

        Route::prefix('results')
            ->name('results.')
            ->group(function () {
                Route::get('/', [StaffResultController::class, 'index'])->name('index');
                Route::post('/lookup', [StaffResultController::class, 'lookup'])->name('lookup');
                Route::get('/bills/{bill}', [StaffResultController::class, 'entry'])->name('entry');
                Route::post('/bills/{bill}', [StaffResultController::class, 'store'])->name('store');
                Route::get('/reports', [StaffResultController::class, 'reports'])->name('reports');
                Route::get('/commission', [StaffResultController::class, 'commission'])->name('commission');
                Route::get('/{result}/print', [StaffResultController::class, 'print'])->name('print');
            });
    });
