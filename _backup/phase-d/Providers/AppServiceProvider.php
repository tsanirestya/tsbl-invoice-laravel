<?php

namespace App\Providers;

use App\Events\DSIImported;
use App\Events\InvoiceFullyPaid;
use App\Events\InvoiceIssued;
use App\Events\PaymentVerified;
use App\Events\ReconciliationApproved;
use App\Events\ReconciliationCreated;
use App\Listeners\DispatchInvoiceEmailOnIssued;
use App\Listeners\DispatchPaymentAllocationOnVerified;
use App\Listeners\GenerateDocumentsOnReconciliationApprove;
use App\Listeners\NotifyFinanceOnReconciliationPending;
use App\Listeners\TriggerReconciliationOnDsiImport;
use App\Listeners\UpdateReservationStatusOnInvoicePaid;
use App\Models\DsiTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Observers\DsiTransactionObserver;
use App\Observers\InvoiceObserver;
use App\Observers\PaymentObserver;
use App\Services\AuditLogService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // ─── C3: Observers ───────────────────────────────────────────────────
        $auditLog = $this->app->make(AuditLogService::class);

        Invoice::observe(new InvoiceObserver($auditLog));
        Payment::observe(new PaymentObserver($auditLog));
        DsiTransaction::observe(new DsiTransactionObserver($auditLog));

        // ─── C2: Event → Listener Mappings ───────────────────────────────────
        Event::listen(DSIImported::class, TriggerReconciliationOnDsiImport::class);
        Event::listen(ReconciliationCreated::class, NotifyFinanceOnReconciliationPending::class);
        Event::listen(ReconciliationApproved::class, GenerateDocumentsOnReconciliationApprove::class);
        Event::listen(InvoiceIssued::class, DispatchInvoiceEmailOnIssued::class);
        Event::listen(InvoiceFullyPaid::class, UpdateReservationStatusOnInvoicePaid::class);
        Event::listen(PaymentVerified::class, DispatchPaymentAllocationOnVerified::class);
    }
}

