<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        return [
            'partner_type'    => 'AGENT',
            'nama_partner'    => $this->faker->company,
            'category'        => 'TRAVEL',
            'channel'         => 'OFFLINE',
            'nama_pt'         => $this->faker->company . ' PT',
            'payment_type'    => 'CREDIT',
            'limit_credit'    => 50000000,
            'is_active'       => true,
            'contract_start'  => now()->subYear(),
            'contract_end'    => now()->addYear(),
        ];
    }
}
