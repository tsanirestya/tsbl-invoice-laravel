<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationAnomaly extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'reservation_id', 'anomaly_type', 'severity', 'detail',
        'score_impact', 'is_resolved', 'resolved_by', 'resolved_at',
        'resolution_notes', 'resolution_type',
    ];

    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'created_at'  => 'datetime',
        ];
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function severityBadge(): string
    {
        return $this->severity === 'CRITICAL' ? 'danger' : 'warning';
    }
}
