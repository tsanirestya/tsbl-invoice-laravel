<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class DsiTransaction extends Model
{
    use HasFactory;
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'batch_id', 'ref_no', 'reservation_id', 'transaction_date',
        'guest_name', 'amount', 'product_description', 'raw_data',
        'is_locked', 'matched_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount'           => 'decimal:2',
            'raw_data'         => 'array',
            'is_locked'        => 'boolean',
            'matched_at'       => 'datetime',
            'created_at'       => 'datetime',
        ];
    }

    /** Block any attribute change once is_locked = true. */
    public function setAttribute($key, $value)
    {
        if ($this->exists && $this->is_locked && $key !== 'is_locked') {
            throw new LogicException("DsiTransaction #{$this->id} is locked — no updates allowed.");
        }

        return parent::setAttribute($key, $value);
    }

    public function batch()
    {
        return $this->belongsTo(DsiImportBatch::class, 'batch_id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function lineItems()
    {
        return $this->hasMany(DsiLineItem::class, 'dsi_transaction_id')->orderBy('sort_order');
    }

    public function duplicateFlags()
    {
        return $this->hasMany(DsiDuplicateFlag::class, 'dsi_transaction_id');
    }

    public function reconciliation()
    {
        return $this->hasOne(Reconciliation::class, 'dsi_transaction_id');
    }
}
