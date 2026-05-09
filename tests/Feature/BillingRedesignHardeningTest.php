<?php

namespace Tests\Feature;

use App\Models\DsiImportBatch;
use App\Models\DsiTransaction;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Reservation;
use App\Services\DsiImporterService;
use App\Services\InvoiceCreatorService;
use App\Services\InvoiceVoidService;
use App\Services\PaymentAllocatorService;
use App\Services\ReconciliationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class BillingRedesignHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Partner $partner;
    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->partner = Partner::create([
            'nama_partner' => 'Test Partner',
            'partner_type' => 'CORPORATE',
            'is_active'    => true,
        ]);
        $this->reservation = Reservation::create([
            'partner_id'     => $this->partner->id,
            'reservation_no' => 'RES-TEST-001',
            'status'         => 'CONFIRMED',
            'visit_date'     => now()->toDateString(),
        ]);
    }

    /** F1: Guard — prevent FINAL invoice void */
    public function test_cannot_void_final_invoice()
    {
        $invoice = Invoice::create([
            'partner_id'   => $this->partner->id,
            'invoice_no'   => 'INV-FINAL-123',
            'invoice_type' => Invoice::TYPE_FINAL,
            'invoice_date' => now()->toDateString(),
            'grand_total'  => 1000,
        ]);

        $service = app(InvoiceVoidService::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only PROFORMA invoices can be voided');

        $service->propose($invoice, 1, 'Mistake');
    }

    /** F2: Guard — prevent double reconciliation */
    public function test_cannot_reconcile_twice()
    {
        $proforma = Invoice::create([
            'partner_id'   => $this->partner->id,
            'invoice_no'   => 'PRO-123',
            'invoice_type' => Invoice::TYPE_PROFORMA,
            'invoice_date' => now()->toDateString(),
            'grand_total'  => 1000,
            'source_type'  => 'RESERVATION',
            'source_id'    => $this->reservation->id,
        ]);

        $batch = DsiImportBatch::create(['status' => 'COMPLETED', 'batch_ref' => 'B1', 'file_hash' => 'H1', 'source' => 'CSV', 'imported_by' => 1]);
        $dsi = DsiTransaction::create([
            'batch_id' => $batch->id,
            'ref_no'   => 'DSI-123',
            'amount'   => 1000,
            'transaction_date' => now()->toDateString(),
        ]);

        $engine = app(ReconciliationEngine::class);
        
        // First reconciliation
        $engine->reconcile($this->reservation, $proforma, $dsi);

        // Second attempt
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already has an active reconciliation');

        $engine->reconcile($this->reservation, $proforma, $dsi);
    }

    /** F3: Guard — prevent DSI re-import (ref_no) */
    public function test_prevent_dsi_re_import_same_ref_no()
    {
        $batch1 = DsiImportBatch::create(['status' => 'COMPLETED', 'batch_ref' => 'B1', 'file_hash' => 'H1', 'source' => 'CSV', 'imported_by' => 1]);
        DsiTransaction::create([
            'batch_id' => $batch1->id,
            'ref_no'   => 'UNIQUE-REF-999',
            'amount'   => 500,
            'transaction_date' => now()->toDateString(),
        ]);

        $importer = app(DsiImporterService::class);
        
        // Attempt to import row with same ref_no
        $rows = [[
            'ref_no' => 'UNIQUE-REF-999',
            'amount' => 500,
            'transaction_date' => now()->toDateString(),
            'guest_name' => 'Duplicate',
        ]];

        $batch2 = $importer->import($rows, 'CSV', 'file2.csv', 'HASH2', 1);

        $this->assertEquals('FAILED', $batch2->status);
        $this->assertStringContainsString("ref_no 'UNIQUE-REF-999' already exists", $batch2->error_summary);
    }

    /** F5: Guard — Credit Note amount guard */
    public function test_credit_note_cannot_exceed_parent_total()
    {
        $final = Invoice::create([
            'partner_id'   => $this->partner->id,
            'invoice_no'   => 'FINAL-100',
            'invoice_type' => Invoice::TYPE_FINAL,
            'invoice_date' => now()->toDateString(),
            'grand_total'  => 100,
        ]);

        $service = app(InvoiceCreatorService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Credit Note amount cannot exceed parent invoice grand_total');

        $service->createCreditNote($final, [], [['amount' => 150, 'description' => 'Too much']], 1);
    }

    /** F11: Guard — Incomplete DSI batch blocks reconciliation */
    public function test_incomplete_dsi_batch_blocks_reconciliation()
    {
        $proforma = Invoice::create([
            'partner_id'   => $this->partner->id,
            'invoice_no'   => 'PRO-456',
            'invoice_type' => Invoice::TYPE_PROFORMA,
            'invoice_date' => now()->toDateString(),
            'grand_total'  => 1000,
            'source_type'  => 'RESERVATION',
            'source_id'    => $this->reservation->id,
        ]);

        $batch = DsiImportBatch::create(['status' => 'PARTIAL', 'batch_ref' => 'B2', 'file_hash' => 'H2', 'source' => 'CSV', 'imported_by' => 1]);
        $dsi = DsiTransaction::create([
            'batch_id' => $batch->id,
            'ref_no'   => 'DSI-456',
            'amount'   => 1000,
            'transaction_date' => now()->toDateString(),
        ]);

        $engine = app(ReconciliationEngine::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('INCOMPLETE_DSI');

        $engine->reconcile($this->reservation, $proforma, $dsi);
    }

    /** F4: Guard — prevent payment over-allocation */
    public function test_cannot_over_allocate_payment()
    {
        $invoice = Invoice::create([
            'partner_id'   => $this->partner->id,
            'invoice_no'   => 'INV-1000',
            'invoice_type' => Invoice::TYPE_FINAL,
            'invoice_date' => now()->toDateString(),
            'grand_total'  => 1000,
        ]);

        $payment = \App\Models\Payment::create([
            'partner_id'         => $this->partner->id,
            'invoice_id'         => $invoice->id,
            'payment_no'         => 'PAY-1000',
            'amount'             => 500,
            'amount_allocated'   => 0,
            'amount_unallocated' => 500,
            'notes'              => '[VERIFIED by #1 at ' . now()->toDateTimeString() . ']',
            'payment_date'       => now()->toDateString(),
        ]);

        $service = app(PaymentAllocatorService::class);
        
        // Try to allocate 1000 from a 500 payment? 
        // Actually the service logic uses min(unallocated, invoiceOwed).
        // So it will only allocate 500.
        $result = $service->allocate($payment, $invoice, 1);
        
        $this->assertEquals(500, $result['allocated']);
        $this->assertEquals(0, $result['overpayment']);
        
        // Now payment has 0 unallocated. Try to allocate again.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('has no unallocated funds');
        
        $service->allocate($payment->fresh(), $invoice->fresh(), 1);
    }

    /** F14: Rollback test — ensure DB consistency on failure */
    public function test_transaction_rollback_on_failure()
    {
        $proforma = Invoice::create([
            'partner_id'   => $this->partner->id,
            'invoice_no'   => 'PRO-FAIL',
            'invoice_type' => Invoice::TYPE_PROFORMA,
            'invoice_date' => now()->toDateString(),
            'grand_total'  => 1000,
            'source_type'  => 'RESERVATION',
            'source_id'    => $this->reservation->id,
        ]);

        $batch = DsiImportBatch::create(['status' => 'COMPLETED', 'batch_ref' => 'B-FAIL', 'file_hash' => 'H-FAIL', 'source' => 'CSV', 'imported_by' => 1]);
        $dsi = DsiTransaction::create([
            'batch_id' => $batch->id,
            'ref_no'   => 'DSI-FAIL',
            'amount'   => 1000,
            'transaction_date' => now()->toDateString(),
        ]);

        // We'll mock the NoShowPolicyService to throw an exception mid-transaction
        $this->mock(\App\Services\NoShowPolicyService::class, function ($mock) {
            $mock->shouldReceive('apply')->andThrow(new \RuntimeException('SIMULATED_FAILURE'));
        });

        $engine = app(ReconciliationEngine::class);

        try {
            $engine->reconcile($this->reservation, $proforma, $dsi);
        } catch (\RuntimeException $e) {
            $this->assertEquals('SIMULATED_FAILURE', $e->getMessage());
        }

        // Verify that Reconciliation record was NOT created
        $this->assertDatabaseMissing('reconciliations', [
            'reservation_id' => $this->reservation->id
        ]);

        // Verify that DSI transaction is NOT locked (it would have been locked at the end)
        $this->assertFalse($dsi->fresh()->is_locked);
    }

    /** F15: Guard — block any update after is_locked=true */
    public function test_locked_records_cannot_be_modified()
    {
        $invoice = Invoice::create([
            'partner_id'   => $this->partner->id,
            'invoice_no'   => 'LOCKED-1',
            'invoice_type' => Invoice::TYPE_FINAL,
            'invoice_date' => now()->toDateString(),
            'grand_total'  => 1000,
            'is_locked'    => true,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('is locked');

        $invoice->update(['grand_total' => 2000]);
    }
}
