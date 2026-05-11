<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Partner;
use App\Models\Reservation;
use App\Models\Invoice;
use App\Models\DsiTransaction;
use App\Models\DsiImportBatch;
use App\Models\Reconciliation;
use App\Services\PaymentRecorderService;
use App\Services\PaymentVerificationService;
use App\Services\PaymentAllocatorService;
use App\Services\ReconciliationEngine;
use App\Services\CreditBalanceService;
use App\Services\DsiDuplicateDetectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

class BillingRedesignPhaseGTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        $this->admin = User::factory()->create(['user_status' => 'ADMIN']);
    }

    /** @test */
    public function it_executes_full_prepaid_flow()
    {
        // 1. Create Reservation
        $partner = Partner::factory()->create(['payment_type' => 'PREPAID']);
        $reservation = Reservation::factory()->create([
            'partner_id' => $partner->id,
            'status'     => 'CONFIRMED'
        ]);

        // 2. Issue Proforma
        $this->actingAs($this->admin)
            ->post("/reservations/{$reservation->id}/issue-proforma", [
                'items' => [
                    [
                        'product_name'  => 'Test Item',
                        'pax'           => 1,
                        'price_per_pax' => 1000000,
                        'amount'        => 1000000,
                    ]
                ],
                'due_date' => now()->addDays(7)->toDateString(),
            ])
            ->assertStatus(302);

        $proforma = Invoice::where('source_id', $reservation->id)
            ->where('invoice_type', 'PROFORMA')
            ->firstOrFail();

        // 3. Receive & Verify Payment
        $payment = app(PaymentRecorderService::class)->record(
            $proforma,
            [
                'payment_date'   => now()->toDateString(),
                'payment_method' => 'TRANSFER',
                'reference_no'   => 'PAY-123'
            ],
            1000000,
            $this->admin->id
        );
        
        app(PaymentVerificationService::class)->verify($payment, $this->admin->id);
        $payment->refresh();

        // 4. Allocate Payment
        app(PaymentAllocatorService::class)->allocate($payment, $proforma, $this->admin->id);
        $proforma->refresh();
        $this->assertEquals('PAID', $proforma->payment_status);

        // 5. DSI Import
        $batch = DsiImportBatch::create([
            'batch_ref' => 'BATCH-PRE',
            'file_name' => 'test.csv', 
            'status'    => 'COMPLETED'
        ]);
        $dsi = DsiTransaction::factory()->create([
            'batch_id'       => $batch->id,
            'reservation_id' => $reservation->id,
            'amount'         => 1000000,
            'ref_no'         => 'REF-PRE',
            'transaction_date' => now()->toDateString()
        ]);

        // 6. Reconcile
        $engine = app(ReconciliationEngine::class);
        $reconciliation = $engine->reconcile($reservation, $proforma, $dsi);

        $this->assertEquals('PENDING_REVIEW', $reconciliation->status);

        // 7. Approve -> Final Invoice
        $this->actingAs($this->admin)
            ->post("/reconciliations/{$reconciliation->id}/approve")
            ->assertStatus(302);

        $finalInvoice = Invoice::where('source_id', $reservation->id)
            ->where('invoice_type', 'FINAL')
            ->firstOrFail();
        
        $this->assertEquals('PAID', $finalInvoice->payment_status);
    }

    /** @test */
    public function it_executes_full_pay_later_flow()
    {
        // 1. Reservation (Pay Later)
        $partner = Partner::factory()->create(['payment_type' => 'CREDIT']);
        $reservation = Reservation::factory()->create([
            'partner_id' => $partner->id,
            'status'     => 'CONFIRMED'
        ]);

        // For Pay Later, we still need a proforma for the current ReconciliationEngine
        $proforma = Invoice::factory()->create([
            'invoice_type' => 'PROFORMA',
            'source_id'    => $reservation->id,
            'source_type'  => Reservation::class,
            'partner_id'   => $partner->id,
            'grand_total'  => 500000,
            'payment_status' => 'UNPAID'
        ]);

        // 2. DSI Import
        $batch = DsiImportBatch::create([
            'batch_ref' => 'BATCH-POST',
            'file_name' => 'test2.csv', 
            'status'    => 'COMPLETED'
        ]);
        $dsi = DsiTransaction::factory()->create([
            'batch_id'       => $batch->id,
            'reservation_id' => $reservation->id,
            'amount'         => 500000,
            'ref_no'         => 'REF-POST',
            'transaction_date' => now()->toDateString()
        ]);

        // 3. Reconcile
        $engine = app(ReconciliationEngine::class);
        $reconciliation = $engine->reconcile($reservation, $proforma, $dsi);

        // 4. Approve -> Final Invoice
        $this->actingAs($this->admin)
            ->post("/reconciliations/{$reconciliation->id}/approve")
            ->assertStatus(302);

        $finalInvoice = Invoice::where('source_id', $reservation->id)
            ->where('invoice_type', 'FINAL')
            ->firstOrFail();

        $this->assertEquals(500000, (float)$finalInvoice->grand_total);
        $this->assertEquals('UNPAID', $finalInvoice->payment_status);
    }

    /** @test */
    public function it_handles_overpayment_and_credit_balance()
    {
        $partner = Partner::factory()->create();
        
        $invoice = Invoice::factory()->create([
            'partner_id'  => $partner->id,
            'grand_total' => 1000000,
            'payment_status' => 'UNPAID'
        ]);

        $payment = app(PaymentRecorderService::class)->record(
            $invoice,
            [
                'payment_date'   => now()->toDateString(),
                'payment_method' => 'TRANSFER',
                'reference_no'   => 'PAY-OVER'
            ],
            1200000,
            $this->admin->id
        );

        app(PaymentVerificationService::class)->verify($payment, $this->admin->id);
        $payment->refresh();

        app(PaymentAllocatorService::class)->allocate($payment, $invoice, $this->admin->id);

        $invoice->refresh();
        $this->assertEquals('PAID', $invoice->payment_status);

        $balance = app(CreditBalanceService::class)->getBalance($partner->id);
        $this->assertEquals(200000, $balance);

        $invoice2 = Invoice::factory()->create([
            'partner_id'  => $partner->id,
            'grand_total' => 500000,
            'payment_status' => 'UNPAID'
        ]);

        $this->actingAs($this->admin)
            ->post("/billing-payments/apply-credit", [
                'partner_id' => $partner->id,
                'invoice_id' => $invoice2->id,
                'amount'     => 200000
            ])->assertStatus(302);

        $invoice2->refresh();
        $this->assertEquals('PARTIAL', $invoice2->payment_status);
        $this->assertEquals(200000, (float)$invoice2->totalPaid());
        $this->assertEquals(0, app(CreditBalanceService::class)->getBalance($partner->id));
    }

    /** @test */
    public function it_detects_dsi_duplicates()
    {
        $batch = DsiImportBatch::create([
            'batch_ref' => 'BATCH-DUP',
            'file_name' => 'batch1.csv', 
            'status'    => 'COMPLETED'
        ]);
        
        $dsi = DsiTransaction::factory()->create([
            'batch_id' => $batch->id,
            'ref_no'   => 'SAME-REF',
            'amount'   => 100000
        ]);

        $detector = app(DsiDuplicateDetectorService::class);
        $this->assertNotNull($detector->findByRefNo('SAME-REF'));
    }
}
