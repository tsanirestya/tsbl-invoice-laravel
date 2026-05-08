<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditClass extends Model
{
    protected $fillable = [
        'name', 'color', 'min_limit', 'max_limit', 'description', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_limit' => 'decimal:2',
            'max_limit' => 'decimal:2',
        ];
    }

    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    /**
     * Return the credit class matching the given limit, or null if none match.
     * Matched when: min_limit <= $limit AND (max_limit is null OR max_limit >= $limit)
     */
    public static function autoAssign(float $limit): ?self
    {
        return static::orderBy('sort_order')
            ->where('min_limit', '<=', $limit)
            ->where(function ($q) use ($limit) {
                $q->whereNull('max_limit')
                  ->orWhere('max_limit', '>=', $limit);
            })
            ->first();
    }
}
