<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use Auditable;

    protected $fillable = [
        'payment_id', 'invoice_id', 'amount_allocated', 'allocated_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_allocated' => 'decimal:2',
        ];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function allocator()
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
