<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Reconciliation extends Model
{
    use Auditable;

    protected $fillable = [
        'reservation_id', 'proforma_invoice_id', 'dsi_transaction_id',
        'status', 'proforma_amount', 'dsi_amount', 'delta_amount',
        'delta_reason', 'no_show_policy_applied', 'no_show_charge_amount',
        'reviewed_by', 'reviewed_at', 'dispute_reason', 'approved_at',
        'final_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'proforma_amount'         => 'decimal:2',
            'dsi_amount'              => 'decimal:2',
            'delta_amount'            => 'decimal:2',
            'no_show_charge_amount'   => 'decimal:2',
            'no_show_policy_applied'  => 'boolean',
            'reviewed_at'             => 'datetime',
            'approved_at'             => 'datetime',
        ];
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function proformaInvoice()
    {
        return $this->belongsTo(Invoice::class, 'proforma_invoice_id');
    }

    public function finalInvoice()
    {
        return $this->belongsTo(Invoice::class, 'final_invoice_id');
    }

    public function dsiTransaction()
    {
        return $this->belongsTo(DsiTransaction::class, 'dsi_transaction_id');
    }

    public function dsiLines()
    {
        return $this->belongsToMany(DsiLineItem::class, 'reconciliation_dsi_lines', 'reconciliation_id', 'dsi_line_item_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
