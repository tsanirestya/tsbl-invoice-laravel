<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Append-only audit logger for manual/service-level events.
 * Model-level auto-auditing is handled by the Auditable trait + AuditLog model.
 * This service is for explicit business-event logging called from services/observers.
 */
class AuditLogService
{
    /**
     * Log a business event against any Eloquent model.
     *
     * @param  Model  $model     The audited model (Invoice, Payment, etc.)
     * @param  string $event     e.g. 'void_proposed', 'reconciliation_approved'
     * @param  array  $oldValues State before the event (nullable)
     * @param  array  $newValues State after the event (nullable)
     * @param  int|null $userId  Override authenticated user (useful in queue jobs)
     */
    public function log(
        Model $model,
        string $event,
        array $oldValues = [],
        array $newValues = [],
        ?int $userId = null
    ): AuditLog {
        return AuditLog::create([
            'user_id'        => $userId ?? Auth::id(),
            'event'          => $event,
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->getKey(),
            'old_values'     => $oldValues ?: null,
            'new_values'     => $newValues ?: null,
            'url'            => request()->fullUrl(),
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }

    /**
     * Log a system-level event not tied to a specific model.
     *
     * @param  string $event
     * @param  string $description  Free-text context
     * @param  int|null $userId
     */
    public function logSystem(string $event, string $description, ?int $userId = null): AuditLog
    {
        return AuditLog::create([
            'user_id'        => $userId ?? Auth::id(),
            'event'          => $event,
            'auditable_type' => 'System',
            'auditable_id'   => 0,
            'old_values'     => null,
            'new_values'     => ['description' => $description],
            'url'            => request()->fullUrl(),
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }

    /**
     * Retrieve audit history for a model, newest first.
     */
    public function historyFor(Model $model): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::where('auditable_type', get_class($model))
            ->where('auditable_id', $model->getKey())
            ->orderByDesc('created_at')
            ->get();
    }
}
