<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class DsiImportBatch extends Model
{
    use Auditable;

    protected $fillable = [
        'batch_ref', 'file_name', 'file_hash', 'source', 'status',
        'total_rows', 'processed_rows', 'failed_rows', 'error_summary',
        'imported_by',
    ];

    public function transactions()
    {
        return $this->hasMany(DsiTransaction::class, 'batch_id');
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
