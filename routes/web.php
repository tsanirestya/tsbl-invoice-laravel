<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
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

// Authenticated storage file proxy (F-013: protect partner legal docs from unauthenticated access)
Route::get('/storage/{path}', function (string $path) {
    $base = storage_path('app/public');
    $file = realpath($base . '/' . $path);
    if ($file === false || !str_starts_with($file, $base) || !is_file($file)) {
        abort(404);
    }
    return response()->file($file);
})->middleware('auth')->where('path', '.*');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // F-009: Password reset request (no email needed — admin mediates)
    Route::get('/forgot-password', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'submitRequest'])->name('password.request.submit')->middleware('throttle:3,60');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // F-009: Force change password (exempt from RequirePasswordChange middleware)
    Route::get('/change-password', [PasswordResetController::class, 'showChangeForm'])->name('password.change.form');
    Route::post('/change-password', [PasswordResetController::class, 'changePassword'])->name('password.change');

    // F-009: Admin — manage password reset requests
    Route::middleware('role:ADMIN')->group(function () {
        Route::get('/admin/password-requests', [PasswordResetController::class, 'listRequests'])->name('admin.password-requests.index');
        Route::post('/admin/password-requests/{user}/resolve', [PasswordResetController::class, 'resolveRequest'])->name('admin.password-requests.resolve');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pending-invoices', [PendingInvoiceController::class, 'index'])->name('pending-invoices.index');

    // Invoice management
    Route::post('invoices/mark-overdue', [InvoiceController::class, 'markOverdue'])->name('invoices.mark-overdue');
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize')->middleware('role:FINANCE,ADMIN');
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
    Route::middleware('role:FINANCE,ADMIN')->group(function () {
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::delete('invoices/{invoice}/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    });

    // Partner management
    Route::get('partners/performance', [PartnerController::class, 'performance'])->name('partners.performance');
    Route::resource('partners', PartnerController::class);

    // Partner deposits
    Route::prefix('partners/{partner}/deposits')->group(function () {
        Route::get('/',       [PartnerDepositController::class, 'index'])->name('deposits.index');
        Route::get('/topup',  [PartnerDepositController::class, 'create'])->name('deposits.create');
        Route::middleware('role:FINANCE,ADMIN')->group(function () {
            Route::post('/',           [PartnerDepositController::class, 'store'])->name('deposits.store');
            Route::post('/adjustment', [PartnerDepositController::class, 'adjustment'])->name('deposits.adjustment');
        });
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
    Route::post('imports/{import}/approve-rows',  [ImportReviewController::class, 'approveRows'])->name('import-review.approve')->middleware('role:FINANCE,ADMIN');
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
    Route::resource('credit-payments', CreditPaymentController::class)->except(['edit', 'update', 'destroy']);
    Route::delete('credit-payments/{creditPayment}', [CreditPaymentController::class, 'destroy'])
        ->name('credit-payments.destroy')
        ->middleware('role:FINANCE,ADMIN');

    Route::middleware('role:ADMIN')->group(function () {
        Route::post('credit-payments/{creditPayment}/confirm-void', [CreditPaymentController::class, 'confirmVoid'])
            ->name('credit-payments.confirm-void');
        Route::post('credit-payments/{creditPayment}/reject-void', [CreditPaymentController::class, 'rejectVoid'])
            ->name('credit-payments.reject-void');
    });

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

        // Audit Trail
        Route::get('audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('admin.audit-logs.index');
        Route::get('audit-logs/{log}', [\App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('admin.audit-logs.show');
    });
});
