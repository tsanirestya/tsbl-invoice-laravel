<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportAnomaly extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_row_id', 'anomaly_type', 'detail', 'severity', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function row()
    {
        return $this->belongsTo(TransactionImportRow::class, 'import_row_id');
    }

    public static function severityFor(string $type): string
    {
        return match ($type) {
            'SUSPICIOUS_PRICING', 'PRICE_MISMATCH' => 'error',
            default => 'warning',
        };
    }
}
