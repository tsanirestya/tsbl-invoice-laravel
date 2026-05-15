<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use Auditable;

    protected $fillable = [
        'reservation_no', 'partner_id', 'guest_name', 'guest_country',
        'pax_adults', 'pax_kids',
        'visit_date', 'status', 'reservation_type', 'payment_method',
        'payment_channel', 'booking_pass_type', 'booking_pass_template_id',
        'booking_pass_file', 'booking_pass_data', 'total_amount', 'notes',
        'latitude', 'longitude', 'location_name', 'is_danger_zone',
        'room_key_photo', 'key_number', 'voucher_photo', 'partner_name_input', 'customer_origin',
        'customer_origin_detail', 'is_spot_check', 'fraud_score_snapshot',
        'ip_address', 'user_agent', 'device_fingerprint', 'qr_token', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date'       => 'date',
            'booking_pass_data'=> 'array',
            'is_danger_zone'   => 'boolean',
            'is_spot_check'    => 'boolean',
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
            'CANCELLED'  => 'secondary',
            'NO_SHOW'    => 'danger',
            'COMPLETED'  => 'primary',
            default      => 'secondary',
        };
    }
}
