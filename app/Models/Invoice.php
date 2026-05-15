<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use Auditable;
    const TYPE_PROFORMA     = 'PROFORMA';
    const TYPE_FINAL        = 'FINAL';
    const TYPE_CREDIT_NOTE  = 'CREDIT_NOTE';
    const TYPE_DEBIT_NOTE   = 'DEBIT_NOTE';
    const TYPE_CANCELLATION = 'CANCELLATION';

    protected $fillable = [
        'invoice_no', 'invoice_type', 'partner_id', 'guest_name', 'visit_date', 'booking_pass_no',
        'invoice_date', 'due_date', 'dsi_transaction_no', 'import_row_id',
        'subtotal', 'deposit', 'grand_total', 'terbilang',
        'payment_status', 'payment_method', 'notes', 'credit_override_reason', 'pdf_path', 'is_finalized',
        'parent_invoice_id', 'replaces_invoice_id', 'delta_amount',
        'source_type', 'source_id', 'is_locked', 'lock_reason',
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
            'delta_amount' => 'decimal:2',
            'is_finalized' => 'boolean',
            'is_locked'    => 'boolean',
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

    public function depositTransaction()
    {
        return $this->hasOne(PartnerDeposit::class)->where('type', 'DEDUCTION');
    }

    public function parentInvoice()
    {
        return $this->belongsTo(Invoice::class, 'parent_invoice_id');
    }

    public function childInvoices()
    {
        return $this->hasMany(Invoice::class, 'parent_invoice_id');
    }

    public function replacesInvoice()
    {
        return $this->belongsTo(Invoice::class, 'replaces_invoice_id');
    }

    public function replacedByInvoice()
    {
        return $this->hasOne(Invoice::class, 'replaces_invoice_id');
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

        if ($total == 0) {
            // Fully covered by deposit — no cash needed
            $status = 'PAID';
        } elseif ($paid >= $total) {
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
