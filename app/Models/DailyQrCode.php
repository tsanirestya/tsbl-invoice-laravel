<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyQrCode extends Model
{
    protected $fillable = [
        'date', 'token', 'qr_image_path', 'is_active', 'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'date'      => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isValidToday(): bool
    {
        return $this->is_active && $this->date->isToday();
    }
}
