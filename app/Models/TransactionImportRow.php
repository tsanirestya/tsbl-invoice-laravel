<?php

namespace App\Models;

use App\Models\ImportAnomaly;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TransactionImportRow extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_id', 'uuid_key', 'row_index',
        'transaction_no', 'date', 'ticket_type', 'ticket_name',
        'transaction_type', 'time', 'cashier', 'payment_method',
        'payment_details', 'unit_price', 'qty', 'total_amount',
        'remark', 'country', 'nationality',
        'matched_product_id', 'match_method',
        'publish_rate', 'nett_price', 'komisi_rate', 'komisi_amount',
        'status', 'is_approved', 'approved_by', 'approved_at', 'override_reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'unit_price'  => 'decimal:2',
            'total_amount'=> 'decimal:2',
            'publish_rate'=> 'decimal:2',
            'nett_price'  => 'decimal:2',
            'komisi_rate' => 'decimal:2',
            'komisi_amount'=> 'decimal:2',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            'created_at'  => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid_key ??= (string) Str::uuid());
    }

    public function import()
    {
        return $this->belongsTo(TransactionImport::class, 'import_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'matched_product_id');
    }

    public function anomalies()
    {
        return $this->hasMany(ImportAnomaly::class, 'import_row_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'import_row_id');
    }
}
