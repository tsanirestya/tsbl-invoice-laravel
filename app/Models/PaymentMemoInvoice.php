<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMemoInvoice extends Model
{
    protected $fillable = [
        'payment_memo_id', 'invoice_id', 'grand_total', 'sisa_tagihan',
    ];

    protected function casts(): array
    {
        return [
            'grand_total'  => 'decimal:2',
            'sisa_tagihan' => 'decimal:2',
        ];
    }

    public function paymentMemo()
    {
        return $this->belongsTo(PaymentMemo::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
