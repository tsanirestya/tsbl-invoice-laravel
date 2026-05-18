<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionReleaseRequest extends Model
{
    protected $fillable = [
        'reservation_payment_id',
        'action',
        'reason',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function reservationPayment()
    {
        return $this->belongsTo(ReservationPayment::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
