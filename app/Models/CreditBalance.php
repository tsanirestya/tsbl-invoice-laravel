<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class CreditBalance extends Model
{
    use Auditable;

    protected $fillable = [
        'partner_id', 'balance', 'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'balance'         => 'decimal:2',
            'last_updated_at' => 'datetime',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function usages()
    {
        return $this->hasMany(CreditBalanceUsage::class);
    }
}
