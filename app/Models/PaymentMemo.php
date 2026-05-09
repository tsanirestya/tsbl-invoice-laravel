<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMemo extends Model
{
    protected $fillable = [
        'memo_no', 'partner_id', 'memo_date', 'payment_deadline', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'memo_date'        => 'date',
            'payment_deadline' => 'date',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memoInvoices()
    {
        return $this->hasMany(PaymentMemoInvoice::class);
    }

    public function totalOutstanding(): float
    {
        return (float) $this->memoInvoices()->sum('sisa_tagihan');
    }

    public function totalCurrentBalance(): float
    {
        return $this->memoInvoices->sum(fn($mi) => $mi->currentBalance());
    }

    public function totalPaidToDate(): float
    {
        return $this->memoInvoices->sum(fn($mi) => $mi->currentPaid());
    }

    /** Generate next memo_no for current month: MP-YYYYMM-001 */
    public static function generateMemoNo(): string
    {
        $prefix = 'MP-' . now()->format('Ym') . '-';
        $last   = static::where('memo_no', 'like', $prefix . '%')
            ->orderByDesc('memo_no')
            ->lockForUpdate()
            ->value('memo_no');

        $seq = $last ? (int) substr($last, -3) + 1 : 1;

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
