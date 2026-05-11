<?php

namespace App\Listeners;

use App\Events\ReconciliationCreated;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notification as BaseNotification;

/**
 * C2 — Listener: NotifyFinanceOnReconciliationPending
 *
 * Handles the ReconciliationCreated event.
 * Notifies all FINANCE and ADMIN users that a reconciliation is awaiting review.
 *
 * Runs asynchronously on the notifications queue.
 */
class NotifyFinanceOnReconciliationPending implements ShouldQueue
{
    public string $queue = 'notifications';
    public int $tries    = 3;

    public function handle(ReconciliationCreated $event): void
    {
        $reconciliation = $event->reconciliation->load(['proformaInvoice.partner']);
        $proforma       = $reconciliation->proformaInvoice;
        $partner        = $proforma?->partner;

        Log::channel('queue')->info('[NotifyFinanceOnReconciliationPending] Notifying finance team', [
            'reconciliation_id' => $reconciliation->id,
            'delta_amount'      => $reconciliation->delta_amount,
        ]);

        // Retrieve all FINANCE and ADMIN users to notify
        $recipients = User::whereIn('role', ['FINANCE', 'ADMIN'])->get();

        if ($recipients->isEmpty()) {
            Log::channel('queue')->warning('[NotifyFinanceOnReconciliationPending] No finance/admin users found');
            return;
        }

        // In-app notification stored in the database notifications table
        // The full Notification class will be implemented in Phase E
        // For now we log the intent
        foreach ($recipients as $user) {
            $user->notifications()->create([
                'id'              => \Illuminate\Support\Str::uuid(),
                'type'            => 'App\Notifications\ReconciliationPendingNotification',
                'notifiable_type' => User::class,
                'notifiable_id'   => $user->id,
                'data'            => json_encode([
                    'reconciliation_id' => $reconciliation->id,
                    'partner_name'      => $partner?->name ?? 'Unknown',
                    'delta_amount'      => $reconciliation->delta_amount,
                    'delta_reason'      => $reconciliation->delta_reason,
                    'url'               => route('reconciliations.show', $reconciliation->id),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::channel('queue')->info('[NotifyFinanceOnReconciliationPending] Notifications created', [
            'recipient_count' => $recipients->count(),
        ]);
    }
}
