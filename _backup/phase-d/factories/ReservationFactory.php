<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'reservation_no' => 'RES-' . $this->faker->unique()->numberBetween(10000, 99999),
            'partner_id'     => Partner::factory(),
            'guest_name'     => $this->faker->name,
            'visit_date'     => now()->addDays(7),
            'booking_source' => 'DIRECT',
            'status'         => 'CONFIRMED',
        ];
    }
}
