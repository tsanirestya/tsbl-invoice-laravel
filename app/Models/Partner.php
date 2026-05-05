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

    public function isContractExpiringSoon(): bool
    {
        if (!$this->contract_end) return false;
        return $this->contract_end->diffInDays(now()) <= 30 && $this->contract_end->isFuture();
    }
}
