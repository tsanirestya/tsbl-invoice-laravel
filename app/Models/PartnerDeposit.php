<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerDeposit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'partner_id', 'type', 'amount', 'invoice_id',
        'reference_no', 'notes', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
