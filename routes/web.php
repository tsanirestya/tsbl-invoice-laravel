<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\DevLoginController;
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
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\PartnerReservationController;
use App\Http\Controllers\SelfServiceController;
use App\Http\Controllers\BookingPassController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\ReservationAnomalyController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// ─── TEMPORARY: No-SSH migration runner ──────────────────────────────────────
// DELETE THIS ROUTE after running migrations on prod.
Route::get('/run-migrations', function () {
    if (request('token') !== '95b994ecf5f6f0225be998f267e03dcd02b51f5fc363426ea8fd21e241247629') {
        abort(403);
    }
    Artisan::call('migrate', ['--force' => true]);
    return '<pre>' . Artisan::output() . '</pre>';
});

Route::get('/clear-cache', function () {
    if (request('token') !== '95b994ecf5f6f0225be998f267e03dcd02b51f5fc363426ea8fd21e241247629') {
        abort(403);
    }
    Artisan::call('view:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    return '<pre>Cache cleared.</pre>';
});
// ─────────────────────────────────────────────────────────────────────────────

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
    Route::get('/dev-login/{role}', [DevLoginController::class, 'login'])->name('dev.login');

    // F-009: Password reset request (no email needed — admin mediates)
    Route::get('/forgot-password', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'submitRequest'])->name('password.request.submit')->middleware('throttle:3,60');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    // Emergency GET logout — for when user is stuck on 403 with no nav
    Route::get('/logout', function () {
        \Illuminate\Support\Facades\Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    })->name('logout.get');

    // Geocode proxy — server-side reverse geocoding agar User-Agent bisa dikirim
    Route::get('/api/geocode/reverse', function (\Illuminate\Http\Request $request) {
        $lat = (float) $request->query('lat');
        $lng = (float) $request->query('lng');
        if (!$lat || !$lng) return response()->json(['error' => 'invalid'], 400);

        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&accept-language=id";
        $ctx = stream_context_create(['http' => [
            'header' => "User-Agent: TSBL-Invoice/1.0\r\nAccept: application/json\r\n",
            'timeout' => 5,
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) return response()->json(['error' => 'fetch_failed'], 502);
        return response($body, 200)->header('Content-Type', 'application/json');
    })->name('geocode.reverse');

    // F-009: Force change password (exempt from RequirePasswordChange middleware)
    Route::get('/change-password', [PasswordResetController::class, 'showChangeForm'])->name('password.change.form');
    Route::post('/change-password', [PasswordResetController::class, 'changePassword'])->name('password.change');

    // ── IT + ADMIN: system & user management ─────────────────────────────────
    Route::middleware('role:ADMIN,IT')->group(function () {
        Route::get('/admin/password-requests', [PasswordResetController::class, 'listRequests'])->name('admin.password-requests.index');
        Route::post('/admin/password-requests/{user}/resolve', [PasswordResetController::class, 'resolveRequest'])->name('admin.password-requests.resolve');
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('admin.audit-logs.index');
        Route::get('audit-logs/{log}', [\App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('admin.audit-logs.show');
        Route::resource('booking-pass-templates', BookingPassController::class)->except(['show']);
        Route::post('booking-pass-templates/{bookingPassTemplate}/upload-bg', [BookingPassController::class, 'uploadBackground'])->name('booking-pass-templates.upload-bg');
        Route::put('booking-pass-templates/{bookingPassTemplate}/field-mapping', [BookingPassController::class, 'updateFieldMapping'])->name('booking-pass-templates.update-mapping');
        Route::get('booking-pass-templates/{bookingPassTemplate}/preview', [BookingPassController::class, 'previewPdf'])->name('booking-pass-templates.preview');
    });

    // ── ADMIN only ────────────────────────────────────────────────────────────
    Route::middleware('role:ADMIN')->group(function () {
        Route::post('settings/dev-mode', [SettingsController::class, 'toggleDevMode'])->name('settings.dev-mode');
        Route::resource('credit-classes', CreditClassController::class)->except(['show']);
        Route::post('commission-review/{payment}/release', [ReservationAnomalyController::class, 'commissionRelease'])->name('commission-review.release');
        Route::post('commission-review/{payment}/revoke', [ReservationAnomalyController::class, 'commissionRevoke'])->name('commission-review.revoke');
        Route::post('credit-payments/{creditPayment}/confirm-void', [CreditPaymentController::class, 'confirmVoid'])->name('credit-payments.confirm-void');
        Route::post('credit-payments/{creditPayment}/reject-void', [CreditPaymentController::class, 'rejectVoid'])->name('credit-payments.reject-void');
        Route::get('admin/employee-partner-checks', [ReservationAnomalyController::class, 'employeeCheckIndex'])->name('admin.employee-partner-checks.index');
        Route::post('admin/employee-partner-checks/run', [ReservationAnomalyController::class, 'employeeCheckRun'])->name('admin.employee-partner-checks.run');
        Route::post('admin/employee-partner-checks/{check}/review', [ReservationAnomalyController::class, 'employeeCheckReview'])->name('admin.employee-partner-checks.review');
        Route::post('partners/{partner}/generate-token', [PartnerController::class, 'generateReservationToken'])->name('partners.generate-token');
        Route::post('partners/{partner}/reset-devices', [PartnerController::class, 'resetDevices'])->name('partners.reset-devices');
        Route::post('partners/{partner}/toggle-suspension', [PartnerController::class, 'toggleReservationSuspension'])->name('partners.toggle-suspension');
    });

    // ── Dashboard ─────────────────────────────────────────────────────────────
    // IT excluded — murni system only
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:ADMIN,BUSDEV_HO,FINANCE_STAFF,FINANCE_MANAGER,BPM,RESERVATION_STAFF,ADMISSION')
        ->name('dashboard');

    // ── Finance: Invoice, Payment, Import ─────────────────────────────────────
    Route::middleware('role:ADMIN,FINANCE_STAFF,FINANCE_MANAGER')->group(function () {
        Route::get('/pending-invoices', [PendingInvoiceController::class, 'index'])->name('pending-invoices.index');
        Route::post('invoices/mark-overdue', [InvoiceController::class, 'markOverdue'])->name('invoices.mark-overdue');
        Route::post('invoices/{invoice}/auto-create-products', [InvoiceController::class, 'autoCreateProducts'])->name('invoices.auto-create-products');
        Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::resource('invoices', InvoiceController::class)->except(['destroy']);
        Route::resource('deposit-invoices', DepositInvoiceController::class)->except(['destroy']);
        Route::get('deposit-invoices/{depositInvoice}/pdf', [DepositInvoiceController::class, 'pdf'])->name('deposit-invoices.pdf');
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::resource('payment-memos', PaymentMemoController::class)->except(['edit', 'update']);
        Route::get('payment-memos/{paymentMemo}/pdf', [PaymentMemoController::class, 'pdf'])->name('payment-memos.pdf');
        Route::get('/api/partners/{partner}/outstanding-invoices', [PaymentMemoController::class, 'outstandingInvoices'])->name('api.partner.outstanding-invoices');
        Route::resource('credit-payments', CreditPaymentController::class)->except(['edit', 'update', 'destroy']);
        Route::get('/api/partners/{partner}/outstanding-invoices-cp', [CreditPaymentController::class, 'outstandingInvoices'])->name('api.partner.outstanding-invoices-cp');
        Route::resource('products', ProductController::class)->except(['show']);
        Route::get('products/{product}/aliases', [ProductAliasController::class, 'index'])->name('product-aliases.index');
        Route::post('products/{product}/aliases', [ProductAliasController::class, 'store'])->name('product-aliases.store');
        Route::delete('products/{product}/aliases/{alias}', [ProductAliasController::class, 'destroy'])->name('product-aliases.destroy');
    });

    // ── Reports: Finance + BusdevHO (read-only) ───────────────────────────────
    Route::middleware('role:ADMIN,FINANCE_STAFF,FINANCE_MANAGER,BUSDEV_HO')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');
        Route::get('reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('reports/export-credit-csv', [ReportController::class, 'exportCreditCsv'])->name('reports.export-credit-csv');
        Route::get('reports/export-credit-pdf', [ReportController::class, 'exportCreditPdf'])->name('reports.export-credit-pdf');
    });

    // ── Anomalies: Finance + BusdevHO + BPM (view) ───────────────────────────
    Route::middleware('role:ADMIN,FINANCE_STAFF,FINANCE_MANAGER,BUSDEV_HO,BPM')->group(function () {
        Route::get('anomalies', [ReservationAnomalyController::class, 'index'])->name('anomalies.index');
        Route::get('anomalies/{anomaly}', [ReservationAnomalyController::class, 'show'])->name('anomalies.show');
        Route::get('commission-review', [ReservationAnomalyController::class, 'commissionIndex'])->name('commission-review.index');
    });

    // ── Finance Manager only: approve actions ─────────────────────────────────
    Route::middleware('role:ADMIN,FINANCE_MANAGER')->group(function () {
        Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::post('deposit-invoices/{depositInvoice}/finalize', [DepositInvoiceController::class, 'finalize'])->name('deposit-invoices.finalize');
        Route::post('deposit-invoices/{depositInvoice}/mark-paid', [DepositInvoiceController::class, 'markPaid'])->name('deposit-invoices.mark-paid');
        Route::post('deposit-invoices/{depositInvoice}/cancel', [DepositInvoiceController::class, 'cancel'])->name('deposit-invoices.cancel');
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::delete('invoices/{invoice}/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
        Route::delete('credit-payments/{creditPayment}', [CreditPaymentController::class, 'destroy'])->name('credit-payments.destroy');
        Route::post('anomalies/{anomaly}/resolve', [ReservationAnomalyController::class, 'resolve'])->name('anomalies.resolve');
        Route::post('imports/{import}/approve-rows', [ImportReviewController::class, 'approveRows'])->name('import-review.approve');
        Route::prefix('partners/{partner}/deposits')->group(function () {
            Route::post('/', [PartnerDepositController::class, 'store'])->name('deposits.store');
            Route::post('/adjustment', [PartnerDepositController::class, 'adjustment'])->name('deposits.adjustment');
        });
    });

    // ── Finance Staff request commission action ───────────────────────────────
    // Finance Staff bisa submit request; Finance Manager + Admin approve/reject via Fase 6 routes
    Route::middleware('role:ADMIN,FINANCE_STAFF,FINANCE_MANAGER')->group(function () {
        Route::post('commission-review/{payment}/request-action', [ReservationAnomalyController::class, 'commissionRequestAction'])->name('commission-review.request-action');
    });
    Route::middleware('role:ADMIN,FINANCE_MANAGER')->group(function () {
        Route::post('commission-requests/{commissionRequest}/approve', [ReservationAnomalyController::class, 'commissionRequestApprove'])->name('commission-requests.approve');
        Route::post('commission-requests/{commissionRequest}/reject', [ReservationAnomalyController::class, 'commissionRequestReject'])->name('commission-requests.reject');
    });

    // ── IT + Finance: Transaction Imports ─────────────────────────────────────
    Route::middleware('role:ADMIN,IT,FINANCE_STAFF,FINANCE_MANAGER')->group(function () {
        Route::resource('imports', TransactionImportController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::post('imports/{import}/reject-rows', [ImportReviewController::class, 'rejectRows'])->name('import-review.reject');
        Route::post('imports/{import}/override-row', [ImportReviewController::class, 'overrideRow'])->name('import-review.override');
        Route::post('imports/{import}/override-group', [ImportReviewController::class, 'overrideGroup'])->name('import-review.override-group');
        Route::post('imports/{import}/reject-group', [ImportReviewController::class, 'rejectGroup'])->name('import-review.reject-group');
        Route::post('imports/{import}/adjust-pricing', [ImportReviewController::class, 'adjustGroupPricing'])->name('import-review.adjust-pricing');
        Route::post('imports/{import}/reassign-product', [ImportReviewController::class, 'reassignProduct'])->name('import-review.reassign-product');
        Route::get('imports/{import}/similar-products', [ImportReviewController::class, 'similarProducts'])->name('import-review.similar-products');
        Route::post('imports/{import}/finalize', [ImportReviewController::class, 'finalizeImport'])->name('import-review.finalize');
        Route::get('imports/{import}/export-anomaly', [ReportController::class, 'exportAnomalyExcel'])->name('imports.export-anomaly');
    });

    // ── Partners: read-only (broad access) ───────────────────────────────────
    Route::middleware('role:ADMIN,BUSDEV_HO,FINANCE_STAFF,FINANCE_MANAGER,BPM,RESERVATION_STAFF')->group(function () {
        Route::get('partners', [PartnerController::class, 'index'])->name('partners.index');
        Route::get('partners/performance', [PartnerController::class, 'performance'])->name('partners.performance');
        Route::get('partners/{partner}', [PartnerController::class, 'show'])->name('partners.show');
        Route::get('/api/partners/{partner}/deposit-balance', [PartnerDepositController::class, 'balance'])->name('api.deposit.balance');
        Route::get('/api/partners/{partner}/credit-info', [PartnerController::class, 'creditInfo'])->name('api.partner.credit-info');
        Route::get('partners/{partner}/deposits', [PartnerDepositController::class, 'index'])->name('deposits.index');
        Route::get('partners/{partner}/deposits/topup', [PartnerDepositController::class, 'create'])->name('deposits.create');
    });

    // ── Partners: write (BPM = non-finansial only, enforced in controller) ────
    Route::middleware('role:ADMIN,BPM')->group(function () {
        Route::get('partners/create', [PartnerController::class, 'create'])->name('partners.create');
        Route::post('partners', [PartnerController::class, 'store'])->name('partners.store');
        Route::get('partners/{partner}/edit', [PartnerController::class, 'edit'])->name('partners.edit');
        Route::put('partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
        Route::delete('partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');
    });

    // ── Reservation (BPM + Reservation Staff) ────────────────────────────────
    Route::middleware('role:ADMIN,BPM,RESERVATION_STAFF')->group(function () {
        Route::get('/api/reservations/search', [ReservationController::class, 'search'])->name('api.reservations.search');
        Route::resource('reservations', ReservationController::class)->except(['destroy']);
        Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
        Route::get('reservations/{reservation}/booking-pass', [ReservationController::class, 'bookingPassDownload'])->name('reservations.booking-pass');
    });

    // ── Admission ─────────────────────────────────────────────────────────────
    Route::middleware('role:ADMIN,ADMISSION')->prefix('admission')->group(function () {
        Route::get('/',        [AdmissionController::class, 'dashboard'])->name('admission.dashboard');
        Route::get('/scan',    [AdmissionController::class, 'scanPage'])->name('admission.scan');
        Route::post('/lookup', [AdmissionController::class, 'lookup'])->name('admission.lookup');
        Route::post('/redeem', [AdmissionController::class, 'redeem'])->name('admission.redeem');
        Route::get('/history', [AdmissionController::class, 'history'])->name('admission.history');
        Route::get('/qr',      [AdmissionController::class, 'qrDisplay'])->name('admission.qr');
    });

    // ── Self-Service QR ───────────────────────────────────────────────────────
    Route::get('self-service-qr', [SelfServiceController::class, 'qrIndex'])->name('self-service.qr-admin');
    Route::post('self-service-qr/generate', [SelfServiceController::class, 'generateQr'])
        ->name('self-service.generate-qr')
        ->middleware('role:ADMIN,ADMISSION');

    // ── Finance: Partner deposits view ────────────────────────────────────────
    Route::middleware('role:ADMIN,FINANCE_STAFF,FINANCE_MANAGER,BPM')->group(function () {
        // topup/store already defined above per role group
    });
});

// ── Phase 10: Public Routes (no auth required) ────────────────────────────────

// Partner self-service reservation (token-based)
Route::middleware(\App\Http\Middleware\ValidateReservationToken::class)->group(function () {
    Route::get('/reserve/{token}', [PartnerReservationController::class, 'form'])->name('partner.reserve.form');
    Route::post('/reserve/{token}', [PartnerReservationController::class, 'store'])->name('partner.reserve.store');
    Route::get('/reserve/{token}/success/{reservationNo}', [PartnerReservationController::class, 'success'])->name('partner.reserve.success');
    Route::get('/reserve/{token}/history', [PartnerReservationController::class, 'history'])->name('partner.reserve.history');
    Route::get('/reserve/{token}/booking-pass/{reservationNo}', [PartnerReservationController::class, 'bookingPassDownload'])->name('partner.reserve.booking-pass');
});

// Self-service QR (public, daily token validation)
// Public geocode proxy for self-service (no auth required)
Route::get('/api/public/geocode/reverse', function (\Illuminate\Http\Request $request) {
    $lat = (float) $request->query('lat');
    $lng = (float) $request->query('lng');
    if (!$lat || !$lng) return response()->json(['error' => 'invalid'], 400);

    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&accept-language=id";
    $ctx = stream_context_create(['http' => [
        'header'  => "User-Agent: TSBL-Invoice/1.0\r\nAccept: application/json\r\n",
        'timeout' => 5,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) return response()->json(['error' => 'fetch_failed'], 502);
    return response($body, 200)->header('Content-Type', 'application/json');
})->name('geocode.public');

Route::get('/self-service/{token}', [SelfServiceController::class, 'scan'])->name('self-service.scan');
Route::post('/self-service/{token}', [SelfServiceController::class, 'store'])->name('self-service.store');
Route::get('/self-service/{token}/success/{reservationNo}', [SelfServiceController::class, 'success'])->name('self-service.success');
Route::get('/self-service/{token}/booking-pass/{reservationNo}', [SelfServiceController::class, 'bookingPassDownload'])->name('self-service.booking-pass');
Route::get('/self-service/{token}/booking-pass/{reservationNo}/view', [SelfServiceController::class, 'bookingPassView'])->name('self-service.booking-pass-view');
