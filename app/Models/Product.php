<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_name', 'partner_type', 'dsi_code', 'category', 'market_type',
        'sub_market_type', 'sub_payment_mode',
        'description', 'default_price', 'publish_rate', 'komisi', 'nett_price', 'unit_price_dsi',
        'unit', 'payment_mode', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'default_price'  => 'decimal:2',
            'publish_rate'   => 'decimal:2',
            'komisi'         => 'decimal:2',
            'nett_price'     => 'decimal:2',
            'unit_price_dsi' => 'decimal:2',
        ];
    }

    public function aliases()
    {
        return $this->hasMany(ProductAlias::class);
    }
}
