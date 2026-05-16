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

    public function isAdmin(): bool      { return $this->user_status === 'ADMIN'; }
    public function isFinance(): bool    { return $this->user_status === 'FINANCE'; }
    public function isSales(): bool      { return $this->user_status === 'SALES'; }
    public function isAdmission(): bool  { return $this->user_status === 'ADMISSION'; }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }
}
