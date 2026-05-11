<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TransactionImport extends Model
{
    protected $fillable = [
        'uuid', 'filename', 'original_filename', 'uploaded_by', 'uploaded_at',
        'status', 'total_rows', 'valid_rows', 'anomaly_rows', 'rejected_rows',
        'processed_at', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at'  => 'datetime',
            'processed_at' => 'datetime',
            'reviewed_at'  => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid ??= (string) Str::uuid());

        // F-027: Cascade delete related rows and rejections when an import is deleted
        static::deleting(function ($import) {
            $import->rows()->delete();
            $import->rejections()->delete();
        });
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function rows()
    {
        return $this->hasMany(TransactionImportRow::class, 'import_id');
    }

    public function rejections()
    {
        return $this->hasMany(ImportRejection::class, 'import_id');
    }

    public function anomalyRate(): float
    {
        if ($this->total_rows === 0) return 0;
        return round($this->anomaly_rows / $this->total_rows * 100, 1);
    }

    public function pendingAnomalies(): int
    {
        return $this->rows()
            ->where('status', 'anomaly')
            ->where('is_approved', false)
            ->count();
    }
}
