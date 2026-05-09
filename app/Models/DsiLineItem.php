<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DsiLineItem extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'dsi_transaction_id', 'description', 'quantity',
        'unit_price', 'amount', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'amount'     => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(DsiTransaction::class, 'dsi_transaction_id');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'dsi_line_item_id');
    }
}
