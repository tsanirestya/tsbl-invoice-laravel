<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationItem extends Model
{
    protected $fillable = [
        'reservation_id', 'product_id', 'product_name',
        'qty', 'price_per_pax', 'amount', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_per_pax' => 'float',
            'amount'        => 'float',
        ];
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
