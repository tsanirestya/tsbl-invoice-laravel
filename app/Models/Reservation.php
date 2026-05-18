<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use Auditable;

    protected $fillable = [
        'reservation_no', 'partner_id', 'guest_name', 'guest_country', 'customer_type',
        'pax_adults', 'pax_kids', 'pax_babies',
        'visit_date', 'status', 'reservation_type', 'payment_method',
        'payment_channel', 'booking_pass_type', 'booking_pass_template_id',
        'booking_pass_file', 'booking_pass_data', 'total_amount', 'notes',
        'latitude', 'longitude', 'location_name', 'is_danger_zone',
        'room_key_photo', 'key_number', 'voucher_photo', 'partner_name_input', 'customer_origin',
        'customer_origin_detail', 'is_spot_check', 'fraud_score_snapshot',
        'ip_address', 'user_agent', 'device_fingerprint', 'qr_token', 'created_by',
        'redeemed_at', 'redeemed_by', 'transaction_match', 'transaction_notes', 'actual_items',
    ];

    protected static function booted()
    {
        static::saving(function ($reservation) {
            $country = trim($reservation->guest_country ?? '');
            if (empty($country) || strcasecmp($country, 'Indonesia') === 0) {
                $reservation->customer_type = 'DOMESTIC';
            } else {
                $reservation->customer_type = 'FOREIGN';
            }
        });
    }

    protected function casts(): array
    {
        return [
            'visit_date'       => 'date',
            'booking_pass_data'=> 'array',
        'actual_items'     => 'array',
        'redeemed_at'      => 'datetime',
            'is_danger_zone'   => 'boolean',
            'is_spot_check'    => 'boolean',
            'pax_adults'       => 'integer',
            'pax_kids'         => 'integer',
            'pax_babies'       => 'integer',
            'total_amount'     => 'float',
            'latitude'         => 'float',
            'longitude'        => 'float',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function items()
    {
        return $this->hasMany(ReservationItem::class)->orderBy('sort_order');
    }

    public function payment()
    {
        return $this->hasOne(ReservationPayment::class);
    }

    public function anomalies()
    {
        return $this->hasMany(ReservationAnomaly::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redeemer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    public function bookingPassTemplate()
    {
        return $this->belongsTo(BookingPassTemplate::class);
    }

    public static function generateReservationNo(): string
    {
        $prefix = 'RES-' . now()->format('Ymd') . '-';
        $last = static::where('reservation_no', 'like', $prefix . '%')
            ->orderByDesc('reservation_no')
            ->value('reservation_no');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function recalcTotal(): void
    {
        $total = $this->items()->sum('amount');
        $this->update(['total_amount' => $total]);
    }

    public function statusBadge(): string
    {
        return match($this->status) {
            'CONFIRMED'  => 'success',
            'PENDING'    => 'warning',
            'REDEEMED'   => 'info',
            'CANCELLED'  => 'secondary',
            'NO_SHOW'    => 'danger',
            'COMPLETED'  => 'primary',
            default      => 'secondary',
        };
    }

    public function transactionMatchBadge(): string
    {
        return match ($this->transaction_match) {
            'MATCH'         => 'success',
            'MISMATCH'      => 'warning',
            'PENDING_CHECK' => 'secondary',
            default         => 'secondary',
        };
    }
}
