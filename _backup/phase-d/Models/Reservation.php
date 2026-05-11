<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'reservation_no', 'partner_id', 'guest_name', 'visit_date',
        'booking_source', 'status', 'confirmed_at', 'cancelled_at',
        'cancel_reason', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date'   => 'date',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'source_id')
            ->where('source_type', self::class);
    }

    public function proformaInvoices()
    {
        return $this->invoices()->where('invoice_type', 'PROFORMA');
    }

    public function dsiTransactions()
    {
        return $this->hasMany(DsiTransaction::class);
    }

    public function reconciliations()
    {
        return $this->hasMany(Reconciliation::class);
    }

    public function reconciliation()
    {
        return $this->hasOne(Reconciliation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
