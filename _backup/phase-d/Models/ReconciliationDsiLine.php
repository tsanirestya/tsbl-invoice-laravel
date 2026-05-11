<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationDsiLine extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'reconciliation_id', 'dsi_line_item_id',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(Reconciliation::class);
    }

    public function dsiLineItem()
    {
        return $this->belongsTo(DsiLineItem::class, 'dsi_line_item_id');
    }
}
