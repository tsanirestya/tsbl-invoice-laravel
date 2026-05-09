<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class DsiDuplicateFlag extends Model
{
    use Auditable;

    protected $fillable = [
        'dsi_transaction_id', 'suspected_duplicate_of', 'detection_layer',
        'status', 'detection_reason', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(DsiTransaction::class, 'dsi_transaction_id');
    }

    public function suspectedDuplicate()
    {
        return $this->belongsTo(DsiTransaction::class, 'suspected_duplicate_of');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
