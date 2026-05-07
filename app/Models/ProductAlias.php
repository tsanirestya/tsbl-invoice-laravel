<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAlias extends Model
{
    protected $fillable = [
        'alias_name', 'product_id', 'created_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
