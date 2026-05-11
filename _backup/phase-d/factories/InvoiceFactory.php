<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'invoice_no'     => 'INV/' . now()->year . '/' . now()->format('m') . '/' . $this->faker->unique()->numberBetween(1000, 9999),
            'invoice_type'   => 'FINAL',
            'partner_id'     => Partner::factory(),
            'guest_name'     => $this->faker->name,
            'visit_date'     => now()->subDays(2),
            'invoice_date'   => now(),
            'due_date'       => now()->addDays(14),
            'subtotal'       => 1000000,
            'grand_total'    => 1000000,
            'payment_status' => 'UNPAID',
            'is_finalized'   => true,
        ];
    }
}
