<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BillRefundController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/verify-report/{reference}', function ($reference) {

    $report = \App\Models\Report::where('report_reference',$reference)->firstOrFail();

    return view('reports.verify', compact('report'));
});

Route::get('/dashboard', function () {
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

