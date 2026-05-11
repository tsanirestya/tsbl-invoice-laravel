<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Partner;
use App\Models\Reservation;
use App\Services\NoShowPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NoShowPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    private NoShowPolicyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NoShowPolicyService();
    }

    /** @test */
    public function it_returns_dsi_amount_if_not_a_no_show()
    {
        $partner = Partner::factory()->make(['nama_partner' => 'Test Partner']);
        $reservation = Reservation::factory()->make(['status' => 'CONFIRMED']);
        $delta = [
            'proforma_amount' => 100000,
            'dsi_amount'      => 100000,
            'delta_amount'    => 0,
        ];

        $result = $this->service->apply($delta, $partner, $reservation);

        $this->assertFalse($result['policy_applied']);
        $this->assertEquals(100000, $result['charge_amount']);
    }

    /** @test */
    public function it_applies_full_charge_policy_on_no_show()
    {
        // Currently resolved policy is 'full_charge' by default
        $partner = Partner::factory()->make(['nama_partner' => 'Test Partner']);
        $reservation = Reservation::factory()->make(['status' => 'NO_SHOW']);
        $delta = [
            'proforma_amount' => 150000,
            'dsi_amount'      => 0,
            'delta_amount'    => -150000,
        ];

        $result = $this->service->apply($delta, $partner, $reservation);

        $this->assertTrue($result['policy_applied']);
        $this->assertEquals('full_charge', $result['policy_type']);
        $this->assertEquals(150000, $result['charge_amount']);
        $this->assertEquals(150000, $result['no_show_charge_amount']);
    }

    /** @test */
    public function it_identifies_no_show_only_if_dsi_amount_is_zero()
    {
        $partner = Partner::factory()->make(['nama_partner' => 'Test Partner']);
        $reservation = Reservation::factory()->make(['status' => 'NO_SHOW']);
        $delta = [
            'proforma_amount' => 150000,
            'dsi_amount'      => 50000, // Guest showed up but spent less? Not a pure "No Show" in this logic
            'delta_amount'    => -100000,
        ];

        $result = $this->service->apply($delta, $partner, $reservation);

        $this->assertFalse($result['policy_applied']);
        $this->assertEquals(50000, $result['charge_amount']);
    }
}
