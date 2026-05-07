<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportRejection extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_id', 'row_index', 'raw_data', 'rejection_reason', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_data'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function import()
    {
        return $this->belongsTo(TransactionImport::class, 'import_id');
    }
}
