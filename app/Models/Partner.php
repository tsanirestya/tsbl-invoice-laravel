<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $fillable = [
        'partner_type', 'nama_partner', 'category', 'channel', 'nama_pt',
        'pic_tsbl', 'pic_partner', 'pic_partner_phone', 'pic_partner_email',
        'address', 'bank_name', 'bank_account_no', 'bank_account_name', 'npwp',
        'payment_type', 'payment_due_days', 'limit_credit',
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
            'limit_credit'   => 'decimal:2',
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
}
