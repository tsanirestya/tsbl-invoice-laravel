<?php

namespace App\Http\Controllers;

use App\Models\CommissionReleaseRequest;
use App\Models\EmployeePartnerCheck;
use App\Models\Partner;
use App\Models\Reservation;
use App\Models\ReservationAnomaly;
use App\Models\ReservationPayment;
use App\Services\EmployeePartnerCheckService;
use Illuminate\Http\Request;

class ReservationAnomalyController extends Controller
{
    public function index(Request $request)
    {
        $query = ReservationAnomaly::with(['reservation.partner', 'resolvedBy'])
            ->orderByRaw('is_resolved ASC')
            ->latest('created_at');

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('type')) {
            $query->where('anomaly_type', $request->type);
        }
        if ($request->boolean('unresolved_only')) {
            $query->where('is_resolved', false);
        }

        $anomalies = $query->paginate(25)->withQueryString();

        // High-risk partners
        $highRiskPartners = Partner::where('fraud_score', '>', 10)
            ->orderByDesc('fraud_score')
            ->limit(10)
            ->get();

        // Commission hold queue
        $heldCommissions = ReservationPayment::where('is_commission_held', true)
            ->whereNull('commission_released_at')
            ->with('reservation.partner')
            ->latest()
            ->get();

        // Stats
        $stats = [
            'critical'  => ReservationAnomaly::where('severity', 'CRITICAL')->where('is_resolved', false)->count(),
            'warning'   => ReservationAnomaly::where('severity', 'WARNING')->where('is_resolved', false)->count(),
            'pending'   => ReservationAnomaly::where('is_resolved', false)->count(),
            'resolved'  => ReservationAnomaly::where('is_resolved', true)->count(),
        ];

        // Employee-partner checks
        $pendingChecks = EmployeePartnerCheck::where('is_reviewed', false)->count();

        $anomalyTypes = ReservationAnomaly::distinct()->pluck('anomaly_type');

        return view('anomalies.index', compact(
            'anomalies', 'highRiskPartners', 'heldCommissions', 'stats', 'pendingChecks', 'anomalyTypes'
        ));
    }

    public function show(ReservationAnomaly $anomaly)
    {
        $anomaly->load(['reservation.partner', 'reservation.items', 'resolvedBy']);
        return view('anomalies.show', compact('anomaly'));
    }

    public function resolve(Request $request, ReservationAnomaly $anomaly)
    {
        $validated = $request->validate([
            'resolution_type'  => 'required|in:CLEARED,CONFIRMED_FRAUD,FALSE_POSITIVE',
            'resolution_notes' => 'required|string|max:1000',
        ]);

        $anomaly->update([
            'is_resolved'      => true,
            'resolved_by'      => auth()->id(),
            'resolved_at'      => now(),
            'resolution_type'  => $validated['resolution_type'],
            'resolution_notes' => $validated['resolution_notes'],
        ]);

        // Recalculate partner fraud score
        if ($anomaly->reservation?->partner) {
            $partner = $anomaly->reservation->partner;
            $score   = ReservationAnomaly::whereHas('reservation', fn($q) => $q->where('partner_id', $partner->id))
                ->where('is_resolved', false)
                ->sum('score_impact');
            $partner->update(['fraud_score' => max(0, (int) $score)]);

            // Unsuspend if score drops below threshold
            if ($score <= 50 && $partner->reservation_suspended) {
                $partner->update(['reservation_suspended' => false, 'reservation_suspended_reason' => null]);
            }
        }

        return back()->with('success', 'Anomali berhasil di-resolve.');
    }

    // ── Commission Review ─────────────────────────────────────────────────────

    public function commissionIndex()
    {
        $held = ReservationPayment::where('is_commission_held', true)
            ->whereNull('commission_released_at')
            ->with(['reservation.partner', 'reservation.items', 'pendingRequest'])
            ->latest()
            ->paginate(20);

        $pendingRequests = CommissionReleaseRequest::where('status', 'pending')
            ->with(['reservationPayment.reservation.partner', 'requestedBy'])
            ->latest()
            ->get();

        return view('commission-review.index', compact('held', 'pendingRequests'));
    }

    // Finance Staff submit request untuk release/revoke komisi
    public function commissionRequestAction(Request $request, ReservationPayment $payment)
    {
        $request->validate([
            'action' => 'required|in:release,revoke',
            'reason' => 'required|string|max:500',
        ]);

        // Cek apakah sudah ada request pending untuk payment ini
        $existing = CommissionReleaseRequest::where('reservation_payment_id', $payment->id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return back()->with('error', 'Sudah ada request pending untuk komisi ini. Tunggu review dari Finance Manager.');
        }

        CommissionReleaseRequest::create([
            'reservation_payment_id' => $payment->id,
            'action'                 => $request->action,
            'reason'                 => $request->reason,
            'status'                 => 'pending',
            'requested_by'           => auth()->id(),
        ]);

        return back()->with('success', 'Request ' . $request->action . ' komisi berhasil diajukan. Menunggu persetujuan Finance Manager.');
    }

    // Finance Manager approve request
    public function commissionRequestApprove(Request $request, CommissionReleaseRequest $commissionRequest)
    {
        if (!$commissionRequest->isPending()) {
            return back()->with('error', 'Request ini sudah diproses sebelumnya.');
        }

        $request->validate(['review_notes' => 'nullable|string|max:500']);

        $payment = $commissionRequest->reservationPayment;

        if ($commissionRequest->action === 'release') {
            $payment->update([
                'is_commission_held'     => false,
                'commission_released_by' => auth()->id(),
                'commission_released_at' => now(),
            ]);
        } else {
            $payment->update([
                'commission_amount'      => 0,
                'is_commission_eligible' => false,
                'is_commission_held'     => false,
                'commission_released_by' => auth()->id(),
                'commission_released_at' => now(),
            ]);
        }

        $commissionRequest->update([
            'status'       => 'approved',
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
            'review_notes' => $request->review_notes,
        ]);

        return back()->with('success', 'Request komisi disetujui dan dieksekusi.');
    }

    // Finance Manager reject request
    public function commissionRequestReject(Request $request, CommissionReleaseRequest $commissionRequest)
    {
        if (!$commissionRequest->isPending()) {
            return back()->with('error', 'Request ini sudah diproses sebelumnya.');
        }

        $request->validate(['review_notes' => 'required|string|max:500']);

        $commissionRequest->update([
            'status'       => 'rejected',
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
            'review_notes' => $request->review_notes,
        ]);

        return back()->with('success', 'Request komisi ditolak.');
    }

    public function commissionRelease(Request $request, ReservationPayment $payment)
    {
        // Route is already protected by role:ADMIN middleware
        $payment->update([
            'is_commission_held'     => false,
            'commission_released_by' => auth()->id(),
            'commission_released_at' => now(),
        ]);

        return back()->with('success', 'Komisi berhasil di-release.');
    }

    public function commissionRevoke(Request $request, ReservationPayment $payment)
    {
        // Route is already protected by role:ADMIN middleware
        $payment->update([
            'commission_amount'      => 0,
            'is_commission_eligible' => false,
            'is_commission_held'     => false,
            'commission_released_by' => auth()->id(),
            'commission_released_at' => now(),
        ]);

        return back()->with('success', 'Komisi berhasil di-revoke (dibatalkan).');
    }

    // ── Employee-Partner Checks ───────────────────────────────────────────────

    public function employeeCheckIndex()
    {
        $checks = EmployeePartnerCheck::with(['partner', 'user', 'reviewedBy'])
            ->orderByRaw('is_reviewed ASC')
            ->latest('created_at')
            ->paginate(25);

        return view('employee-partner-checks.index', compact('checks'));
    }

    public function employeeCheckRun(EmployeePartnerCheckService $service)
    {
        $matches = $service->runFullCheck();
        return back()->with('success', count($matches) . ' kecocokan ditemukan dan disimpan.');
    }

    public function employeeCheckReview(Request $request, EmployeePartnerCheck $check)
    {
        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:500',
        ]);

        $check->update([
            'is_reviewed'  => true,
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        return back()->with('success', 'Review tersimpan.');
    }
}
