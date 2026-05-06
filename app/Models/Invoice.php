<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_no', 'partner_id', 'guest_name', 'visit_date', 'booking_pass_no',
        'invoice_date', 'due_date', 'dsi_transaction_no',
        'subtotal', 'deposit', 'grand_total', 'terbilang',
        'payment_status', 'notes', 'pdf_path', 'is_finalized',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date'   => 'date',
            'invoice_date' => 'date',
            'due_date'     => 'date',
            'subtotal'     => 'decimal:2',
            'deposit'      => 'decimal:2',
            'grand_total'  => 'decimal:2',
            'is_finalized' => 'boolean',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function logs()
    {
        return $this->hasMany(InvoiceLog::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->payment_status !== 'PAID';
    }

    public static function syncOverdueStatuses(): void
    {
        static::query()
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['payment_status' => 'OVERDUE']);
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function recalcStatus(): void
    {
        $paid  = $this->totalPaid();
        $total = (float) $this->grand_total;

        if ($paid >= $total && $total > 0) {
            $status = 'PAID';
        } elseif ($paid > 0) {
            $status = 'PARTIAL';
        } elseif ($this->due_date && $this->due_date->isPast()) {
            $status = 'OVERDUE';
        } else {
            $status = 'UNPAID';
        }

        $this->update(['payment_status' => $status]);
    }
}
