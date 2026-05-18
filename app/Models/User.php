<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'full_name', 'email', 'phone', 'password',
        'user_status', 'signature_image', 'position_name', 'is_active',
        'password_change_required', 'reset_requested_at',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password'                => 'hashed',
            'is_active'               => 'boolean',
            'password_change_required'=> 'boolean',
            'reset_requested_at'      => 'datetime',
        ];
    }

    public function isAdmin(): bool             { return $this->user_status === 'ADMIN'; }
    public function isIT(): bool                { return $this->user_status === 'IT'; }
    public function isBusdevHO(): bool          { return $this->user_status === 'BUSDEV_HO'; }
    public function isFinanceStaff(): bool      { return $this->user_status === 'FINANCE_STAFF'; }
    public function isFinanceManager(): bool    { return $this->user_status === 'FINANCE_MANAGER'; }
    public function isBPM(): bool               { return $this->user_status === 'BPM'; }
    public function isReservationStaff(): bool  { return $this->user_status === 'RESERVATION_STAFF'; }
    public function isAdmission(): bool         { return $this->user_status === 'ADMISSION'; }

    public function canApproveFinance(): bool
    {
        return in_array($this->user_status, ['ADMIN', 'FINANCE_MANAGER']);
    }

    public function canAccessFinance(): bool
    {
        return in_array($this->user_status, ['ADMIN', 'FINANCE_STAFF', 'FINANCE_MANAGER']);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }
}
