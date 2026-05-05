<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id', 'product_id', 'product_name', 'pax', 'price_per_pax', 'amount', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_per_pax' => 'decimal:2',
            'amount'        => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
