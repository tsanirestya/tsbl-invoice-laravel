<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->logAudit('created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getChanges());
            $newValues = $model->getChanges();
            
            // Don't log if no meaningful changes
            if (empty($newValues)) return;

            $model->logAudit('updated', $oldValues, $newValues);
        });

        static::deleted(function ($model) {
            $model->logAudit('deleted', $model->getAttributes(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->logAudit('restored', null, $model->getAttributes());
            });
        }
    }

    protected function logAudit(string $event, ?array $oldValues, ?array $newValues)
    {
        // Remove sensitive fields
        $hidden = ['password', 'remember_token'];
        if ($oldValues) $oldValues = array_diff_key($oldValues, array_flip($hidden));
        if ($newValues) $newValues = array_diff_key($newValues, array_flip($hidden));

        AuditLog::create([
            'user_id'        => Auth::id(),
            'event'          => $event,
            'auditable_type' => get_class($this),
            'auditable_id'   => $this->id,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'url'            => request()->fullUrl(),
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }
}
