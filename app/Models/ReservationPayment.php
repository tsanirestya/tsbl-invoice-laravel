<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationPayment extends Model
{
    protected $fillable = [
        'reservation_id', 'payment_method', 'payment_channel',
        'gross_amount', 'nett_amount', 'commission_amount', 'commission_rate',
        'is_commission_eligible', 'payment_status', 'proof_file',
        'is_commission_held', 'commission_hold_reason',
        'commission_released_by', 'commission_released_at',
        'verified_by', 'verified_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_commission_eligible' => 'boolean',
            'is_commission_held'     => 'boolean',
            'gross_amount'           => 'float',
            'nett_amount'            => 'float',
            'commission_amount'      => 'float',
            'commission_rate'        => 'float',
            'commission_released_at' => 'datetime',
            'verified_at'            => 'datetime',
        ];
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function commissionReleasedBy()
    {
        return $this->belongsTo(User::class, 'commission_released_by');
    }

    public function commissionRequests()
    {
        return $this->hasMany(CommissionReleaseRequest::class);
    }

    public function pendingRequest()
    {
        return $this->hasOne(CommissionReleaseRequest::class)->where('status', 'pending');
    }
}
