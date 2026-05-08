<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositInvoiceController;
use App\Http\Controllers\ImportReviewController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PartnerDepositController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PendingInvoiceController;
use App\Http\Controllers\ProductAliasController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionImportController;
use App\Http\Controllers\CreditClassController;
use App\Http\Controllers\CreditPaymentController;
use App\Http\Controllers\PaymentMemoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/dashboard'));

// Temporary route to run migrations and clear cache since SSH is unavailable
Route::get('/setup-production', function() {
    $output = '<b>Setup started...</b><br><br>';
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output .= "Migration Output:<br>" . nl2br(\Illuminate\Support\Facades\Artisan::output() ?: '(no output)') . "<br><br>";

        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        $output .= "Optimize Clear Output:<br>" . nl2br(\Illuminate\Support\Facades\Artisan::output() ?: '(no output)') . "<br><br>";

        \Illuminate\Support\Facades\Artisan::call('optimize');
        $output .= "Optimize Output:<br>" . nl2br(\Illuminate\Support\Facades\Artisan::output() ?: '(no output)') . "<br><br>";

        return response($output . "<b>Setup Complete!</b>")->header('Content-Type', 'text/html');
    } catch (\Throwable $e) {
        return response("<b>Error:</b> " . $e->getMessage() . "<br><pre>" . $e->getTraceAsString() . "</pre>")->header('Content-Type', 'text/html');
    }
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pending-invoices', [PendingInvoiceController::class, 'index'])->name('pending-invoices.index');

    // Invoice management
    Route::post('invoices/mark-overdue', [InvoiceController::class, 'markOverdue'])->name('invoices.mark-overdue');
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
    Route::post('invoices/{invoice}/auto-create-products', [InvoiceController::class, 'autoCreateProducts'])->name('invoices.auto-create-products');
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Deposit Invoice management (invoice permintaan deposit ke partner)
    Route::resource('deposit-invoices', DepositInvoiceController::class);
    Route::post('deposit-invoices/{depositInvoice}/finalize', [DepositInvoiceController::class, 'finalize'])->name('deposit-invoices.finalize');
    Route::post('deposit-invoices/{depositInvoice}/mark-paid', [DepositInvoiceController::class, 'markPaid'])->name('deposit-invoices.mark-paid');
    Route::post('deposit-invoices/{depositInvoice}/cancel', [DepositInvoiceController::class, 'cancel'])->name('deposit-invoices.cancel');
    Route::get('deposit-invoices/{depositInvoice}/pdf', [DepositInvoiceController::class, 'pdf'])->name('deposit-invoices.pdf');

    // Payment management
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('invoices/{invoice}/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    // Partner management
    Route::get('partners/performance', [PartnerController::class, 'performance'])->name('partners.performance');
    Route::resource('partners', PartnerController::class);

    // Partner deposits
    Route::prefix('partners/{partner}/deposits')->group(function () {
        Route::get('/',       [PartnerDepositController::class, 'index'])->name('deposits.index');
        Route::get('/topup',  [PartnerDepositController::class, 'create'])->name('deposits.create');
        Route::post('/',      [PartnerDepositController::class, 'store'])->name('deposits.store');
        Route::post('/adjustment', [PartnerDepositController::class, 'adjustment'])->name('deposits.adjustment');
    });

    // AJAX — deposit balance for invoice form
    Route::get('/api/partners/{partner}/deposit-balance', [PartnerDepositController::class, 'balance'])
        ->name('api.deposit.balance');

    // AJAX — credit info for invoice form
    Route::get('/api/partners/{partner}/credit-info', [PartnerController::class, 'creditInfo'])
        ->name('api.partner.credit-info');

    // Product management
    Route::resource('products', ProductController::class)->except(['show']);

    // Product Aliases
    Route::get('products/{product}/aliases', [ProductAliasController::class, 'index'])->name('product-aliases.index');
    Route::post('products/{product}/aliases', [ProductAliasController::class, 'store'])->name('product-aliases.store');
    Route::delete('products/{product}/aliases/{alias}', [ProductAliasController::class, 'destroy'])->name('product-aliases.destroy');

    // Transaction Imports
    Route::resource('imports', TransactionImportController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    // Import Review actions
    Route::post('imports/{import}/approve-rows',  [ImportReviewController::class, 'approveRows'])->name('import-review.approve');
    Route::post('imports/{import}/reject-rows',   [ImportReviewController::class, 'rejectRows'])->name('import-review.reject');
    Route::post('imports/{import}/override-row',  [ImportReviewController::class, 'overrideRow'])->name('import-review.override');
    Route::post('imports/{import}/override-group',[ImportReviewController::class, 'overrideGroup'])->name('import-review.override-group');
    Route::post('imports/{import}/reject-group',    [ImportReviewController::class, 'rejectGroup'])->name('import-review.reject-group');
    Route::post('imports/{import}/adjust-pricing',  [ImportReviewController::class, 'adjustGroupPricing'])->name('import-review.adjust-pricing');
    Route::post('imports/{import}/reassign-product',[ImportReviewController::class, 'reassignProduct'])->name('import-review.reassign-product');
    Route::get('imports/{import}/similar-products', [ImportReviewController::class, 'similarProducts'])->name('import-review.similar-products');
    Route::post('imports/{import}/finalize',        [ImportReviewController::class, 'finalizeImport'])->name('import-review.finalize');

    // Anomaly export Excel (per import session)
    Route::get('imports/{import}/export-anomaly', [ReportController::class, 'exportAnomalyExcel'])->name('imports.export-anomaly');

    // Payment Memos
    Route::resource('payment-memos', PaymentMemoController::class)->except(['edit', 'update']);
    Route::get('payment-memos/{paymentMemo}/pdf', [PaymentMemoController::class, 'pdf'])->name('payment-memos.pdf');
    Route::get('/api/partners/{partner}/outstanding-invoices', [PaymentMemoController::class, 'outstandingInvoices'])
        ->name('api.partner.outstanding-invoices');

    // Batch Credit Payments
    Route::resource('credit-payments', CreditPaymentController::class)->except(['edit', 'update']);
    Route::get('/api/partners/{partner}/outstanding-invoices-cp', [CreditPaymentController::class, 'outstandingInvoices'])
        ->name('api.partner.outstanding-invoices-cp');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');
    Route::get('reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::get('reports/export-credit-csv', [ReportController::class, 'exportCreditCsv'])->name('reports.export-credit-csv');
    Route::get('reports/export-credit-pdf', [ReportController::class, 'exportCreditPdf'])->name('reports.export-credit-pdf');

    // Admin-only
    Route::middleware('role:ADMIN')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('credit-classes', CreditClassController::class)->except(['show']);
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
