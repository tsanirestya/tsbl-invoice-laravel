<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditBalanceUsage extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'credit_balance_id', 'invoice_id', 'payment_id',
        'type', 'amount', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function creditBalance()
    {
        return $this->belongsTo(CreditBalance::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
