<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_name', 'description', 'default_price', 'unit', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'default_price' => 'decimal:2',
        ];
    }
}
