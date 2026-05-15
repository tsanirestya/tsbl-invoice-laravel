<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPassTemplate extends Model
{
    protected $fillable = [
        'partner_id', 'template_name', 'template_file',
        'field_mapping', 'is_active', 'created_by',
        'qr_type', 'template_type',
    ];

    protected function casts(): array
    {
        return [
            'field_mapping' => 'array',
            'is_active'     => 'boolean',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
