<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditPayment extends Model
{
    protected $fillable = [
        'partner_id', 'batch_no', 'payment_date', 'total_received',
        'total_allocated', 'excess_to_deposit', 'deposit_transaction_id',
        'payment_method', 'reference_no', 'proof_file', 'notes',
        'is_voided', 'voided_at', 'voided_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date'      => 'date',
            'total_received'    => 'decimal:2',
            'total_allocated'   => 'decimal:2',
            'excess_to_deposit' => 'decimal:2',
            'is_voided'         => 'boolean',
            'voided_at'         => 'datetime',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function invoicePayments()
    {
        return $this->hasMany(Payment::class, 'credit_payment_id');
    }

    public function depositTransaction()
    {
        return $this->belongsTo(PartnerDeposit::class, 'deposit_transaction_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public static function generateBatchNo(): string
    {
        $prefix = 'CP-' . now()->format('Ym') . '-';
        $last   = static::where('batch_no', 'like', $prefix . '%')
            ->latest('id')
            ->lockForUpdate()
            ->value('batch_no');
        $seq = $last ? ((int) substr($last, -3)) + 1 : 1;
        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
