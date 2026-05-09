<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use SoftDeletes, Auditable;
    protected $fillable = [
        'partner_type', 'nama_partner', 'category', 'channel', 'nama_pt',
        'pic_tsbl', 'pic_partner', 'pic_partner_phone', 'pic_partner_email',
        'address', 'bank_name', 'bank_account_no', 'bank_account_name', 'npwp',
        'payment_type', 'payment_due_days', 'limit_credit', 'credit_class_id',
        'contract_start', 'contract_end',
        'doc_akta_pendirian', 'doc_akta_perubahan', 'doc_surat_kuasa',
        'doc_ktp', 'doc_nib', 'doc_npwp',
        'notes', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'contract_start' => 'date',
            'contract_end'   => 'date',
            'limit_credit'   => 'integer',
        ];
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function deposits()
    {
        return $this->hasMany(PartnerDeposit::class);
    }

    public function creditClass()
    {
        return $this->belongsTo(CreditClass::class);
    }

    public function creditPayments()
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function depositBalance(): float
    {
        return (float) ($this->deposits()
            ->selectRaw("SUM(CASE WHEN type='TOPUP' THEN amount WHEN type='DEDUCTION' THEN -amount WHEN type='ADJUSTMENT' THEN amount ELSE 0 END) as balance")
            ->value('balance') ?? 0);
    }

    public function hasEnoughDeposit(float $amount): bool
    {
        return $this->depositBalance() >= $amount;
    }

    public function depositInfo(): array
    {
        $balance   = $this->depositBalance();
        $threshold = (float) \App\Models\Setting::get('deposit_low_threshold', 1000000);

        return [
            'balance'           => $balance,
            'balance_formatted' => 'Rp ' . number_format($balance, 0, ',', '.'),
            'threshold'         => $threshold,
            'is_low'            => $balance > 0 && $balance < $threshold,
            'is_empty'          => $balance <= 0,
        ];
    }

    public function isContractExpiringSoon(): bool
    {
        if (!$this->contract_end) return false;
        return $this->contract_end->diffInDays(now()) <= 30 && $this->contract_end->isFuture();
    }

    /** SUM grand_total of all unpaid/partial/overdue invoices */
    public function creditUsed(): float
    {
        return (float) $this->invoices()
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])
            ->sum('grand_total');
    }

    public function creditAvailable(): float
    {
        return (float) $this->limit_credit - $this->creditUsed();
    }

    /** Returns 0 if limit_credit is 0 to avoid division by zero */
    public function creditUtilizationPercent(): float
    {
        $limit = (float) $this->limit_credit;
        if ($limit <= 0) return 0;
        return ($this->creditUsed() / $limit) * 100;
    }

    /** Returns NORMAL | WARNING | OVER_LIMIT based on setting credit_warning_threshold */
    public function creditStatus(): string
    {
        $util      = $this->creditUtilizationPercent();
        $threshold = (float) Setting::get('credit_warning_threshold', 80);

        if ($util > 100) return 'OVER_LIMIT';
        if ($util >= $threshold) return 'WARNING';
        return 'NORMAL';
    }

    public function creditInfo(): array
    {
        $limit      = (float) $this->limit_credit;
        $used       = $this->creditUsed();
        $available  = $limit - $used;
        $util       = $this->creditUtilizationPercent();
        $status     = $this->creditStatus();

        return [
            'limit'               => $limit,
            'used'                => $used,
            'available'           => $available,
            'utilization_percent' => round($util, 2),
            'status'              => $status,
            'credit_class_id'     => $this->credit_class_id,
            'credit_class_name'   => $this->creditClass?->name,
            'credit_class_color'  => $this->creditClass?->color,
            'limit_formatted'     => 'Rp ' . number_format($limit, 0, ',', '.'),
            'used_formatted'      => 'Rp ' . number_format($used, 0, ',', '.'),
            'available_formatted' => 'Rp ' . number_format($available, 0, ',', '.'),
        ];
    }

    public function scopeCreditPartners($query)
    {
        return $query->where('limit_credit', '>', 0);
    }

    /** Partners where SUM of unpaid invoice grand_total exceeds their limit_credit */
    public function scopeOverCreditLimit($query)
    {
        return $query->creditPartners()->whereRaw(
            '(SELECT COALESCE(SUM(grand_total), 0) FROM invoices WHERE invoices.partner_id = partners.id AND invoices.payment_status IN (?,?,?)) > limit_credit',
            ['UNPAID', 'PARTIAL', 'OVERDUE']
        );
    }
}
