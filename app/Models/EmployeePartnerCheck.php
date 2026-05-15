<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePartnerCheck extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'partner_id', 'user_id', 'match_type', 'match_detail',
        'is_reviewed', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_reviewed' => 'boolean',
            'reviewed_at' => 'datetime',
            'created_at'  => 'datetime',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
