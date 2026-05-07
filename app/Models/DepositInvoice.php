<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositInvoice extends Model
{
    protected $fillable = [
        'invoice_no', 'partner_id', 'invoice_date', 'due_date',
        'amount', 'terbilang', 'status', 'notes',
        'pdf_path', 'is_finalized', 'deposit_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date'     => 'date',
            'amount'       => 'decimal:2',
            'is_finalized' => 'boolean',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function depositRecord()
    {
        return $this->belongsTo(PartnerDeposit::class, 'deposit_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'CANCELLED';
    }
}
