<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\DsiTransaction;
use App\Services\DeltaCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeltaCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private DeltaCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeltaCalculatorService();
    }

    /** @test */
    public function it_calculates_zero_delta()
    {
        $proforma = new Invoice(['grand_total' => 100000]);
        $dsi = new DsiTransaction(['amount' => 100000]);

        $result = $this->service->calculate($proforma, $dsi);

        $this->assertEquals(0, $result['delta_amount']);
        $this->assertEquals('ZERO', $result['delta_type']);
        $this->assertFalse($result['requires_adjustment']);
    }

    /** @test */
    public function it_calculates_positive_delta_over()
    {
        $proforma = new Invoice(['grand_total' => 100000]);
        $dsi = new DsiTransaction(['amount' => 120000]);

        $result = $this->service->calculate($proforma, $dsi);

        $this->assertEquals(20000, $result['delta_amount']);
        $this->assertEquals('OVER', $result['delta_type']);
        $this->assertTrue($result['requires_adjustment']);
    }

    /** @test */
    public function it_calculates_negative_delta_under()
    {
        $proforma = new Invoice(['grand_total' => 100000]);
        $dsi = new DsiTransaction(['amount' => 80000]);

        $result = $this->service->calculate($proforma, $dsi);

        $this->assertEquals(-20000, $result['delta_amount']);
        $this->assertEquals('UNDER', $result['delta_type']);
        $this->assertTrue($result['requires_adjustment']);
    }

    /** @test */
    public function it_handles_floating_point_precision()
    {
        $proforma = new Invoice(['grand_total' => 100.05]);
        $dsi = new DsiTransaction(['amount' => 100.0500001]);

        $result = $this->service->calculate($proforma, $dsi);

        $this->assertEquals('ZERO', $result['delta_type']);
        $this->assertEquals(0, $result['delta_amount']);
    }

    /** @test */
    public function it_calculates_raw_amounts()
    {
        $result = $this->service->calculateRaw(500.00, 450.00);

        $this->assertEquals(-50.00, $result['delta_amount']);
        $this->assertEquals('UNDER', $result['delta_type']);
        $this->assertTrue($result['requires_adjustment']);
    }
}
