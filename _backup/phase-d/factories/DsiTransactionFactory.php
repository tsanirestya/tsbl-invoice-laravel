<?php

namespace Database\Factories;

use App\Models\DsiTransaction;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

class DsiTransactionFactory extends Factory
{
    protected $model = DsiTransaction::class;

    public function definition(): array
    {
        return [
            'batch_id'             => 1,
            'ref_no'               => 'DSI-' . $this->faker->unique()->numberBetween(100000, 999999),
            'reservation_id'       => Reservation::factory(),
            'transaction_date'     => now()->subDays(2),
            'guest_name'           => $this->faker->name,
            'amount'               => 1000000,
            'product_description'  => 'Adventure Pack',
            'is_locked'            => false,
            'created_at'           => now(),
        ];
    }
}
