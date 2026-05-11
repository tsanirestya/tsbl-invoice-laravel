<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Invoice;
use App\Services\InvoiceNumberGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceNumberGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceNumberGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceNumberGeneratorService();
    }

    /** @test */
    public function it_generates_correct_prefix_for_each_type()
    {
        $types = [
            'PROFORMA'     => 'PRF',
            'FINAL'        => 'INV',
            'CREDIT_NOTE'  => 'CN',
            'DEBIT_NOTE'   => 'DN',
            'CANCELLATION' => 'VOID',
        ];

        foreach ($types as $type => $expectedPrefix) {
            $number = $this->service->generate($type);
            $this->assertStringStartsWith($expectedPrefix . '/', $number);
        }
    }

    /** @test */
    public function it_increments_sequence_correct_ly()
    {
        Carbon::setTestNow('2026-05-09');

        // First generation
        $num1 = $this->service->generate('FINAL');
        $this->assertEquals('INV/2026/05/0001', $num1);

        // Manually create an invoice to ensure it picks up from there
        Invoice::factory()->create(['invoice_no' => 'INV/2026/05/0001', 'invoice_type' => 'FINAL']);

        // Second generation
        $num2 = $this->service->generate('FINAL');
        $this->assertEquals('INV/2026/05/0002', $num2);

        // Third generation
        Invoice::factory()->create(['invoice_no' => 'INV/2026/05/0002', 'invoice_type' => 'FINAL']);
        $num3 = $this->service->generate('FINAL');
        $this->assertEquals('INV/2026/05/0003', $num3);

        Carbon::setTestNow();
    }

    /** @test */
    public function it_handles_gaps_and_mixed_formats()
    {
        Carbon::setTestNow('2026-05-09');

        // Create high number
        Invoice::factory()->create(['invoice_no' => 'INV/2026/05/0099', 'invoice_type' => 'FINAL']);

        $next = $this->service->generate('FINAL');
        $this->assertEquals('INV/2026/05/0100', $next);

        Carbon::setTestNow();
    }

    /** @test */
    public function it_resets_sequence_on_new_month()
    {
        // May 2026
        Carbon::setTestNow('2026-05-31');
        Invoice::factory()->create(['invoice_no' => 'INV/2026/05/0005', 'invoice_type' => 'FINAL']);
        
        // June 2026
        Carbon::setTestNow('2026-06-01');
        $next = $this->service->generate('FINAL');
        $this->assertEquals('INV/2026/06/0001', $next);

        Carbon::setTestNow();
    }
}
