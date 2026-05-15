<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\Reservation;
use App\Models\ReservationAnomaly;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class AnomalyDetectionService
{
    public function __construct(private DangerZoneService $dangerZone) {}

    public function check(Reservation $reservation): array
    {
        $anomalies = [];

        if ($reservation->reservation_type !== 'PARTNER') {
            return [];
        }

        $anomalies = array_merge($anomalies, $this->checkLastMinuteBooking($reservation));
        $anomalies = array_merge($anomalies, $this->checkSameDayPattern($reservation));
        $anomalies = array_merge($anomalies, $this->checkUnusualHours($reservation));
        $anomalies = array_merge($anomalies, $this->checkDangerZone($reservation));
        $anomalies = array_merge($anomalies, $this->checkStationaryPattern($reservation));
        $anomalies = array_merge($anomalies, $this->checkImpossibleTravel($reservation));
        $anomalies = array_merge($anomalies, $this->checkHighOnTheSpotRatio($reservation));
        $anomalies = array_merge($anomalies, $this->checkCommissionAnomaly($reservation));
        $anomalies = array_merge($anomalies, $this->checkVolumeSpike($reservation));
        $anomalies = array_merge($anomalies, $this->checkDummyPartnerSuspect($reservation));
        $anomalies = array_merge($anomalies, $this->checkCustomerWalkInClaim($reservation));

        $this->saveAnomalies($reservation, $anomalies);

        if ($reservation->partner) {
            $this->updateFraudScore($reservation->partner);
            $this->applyAutoActions($reservation->partner);
        }

        return $anomalies;
    }

    // ── Layer 1: Temporal ─────────────────────────────────────────────────────

    private function checkLastMinuteBooking(Reservation $reservation): array
    {
        if (!$reservation->is_danger_zone) return [];
        $hoursBeforeVisit = Carbon::now()->diffInHours(Carbon::parse($reservation->visit_date), false);
        if ($hoursBeforeVisit >= 0 && $hoursBeforeVisit < 2) {
            return [[
                'type'     => 'LAST_MINUTE_BOOKING',
                'severity' => 'CRITICAL',
                'score'    => 10,
                'detail'   => "Reservasi dibuat < 2 jam sebelum kunjungan ({$hoursBeforeVisit} jam) dari dalam danger zone.",
            ]];
        }
        return [];
    }

    private function checkSameDayPattern(Reservation $reservation): array
    {
        if (!$reservation->partner_id) return [];
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $total = Reservation::where('partner_id', $reservation->partner_id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();
        if ($total < 10) return []; // Not enough data

        $sameDayCount = Reservation::where('partner_id', $reservation->partner_id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->whereRaw('DATE(visit_date) = DATE(created_at)')
            ->count();

        if ($total > 0 && ($sameDayCount / $total) > 0.60) {
            $pct = round(($sameDayCount / $total) * 100);
            return [[
                'type'     => 'SAME_DAY_PATTERN',
                'severity' => 'WARNING',
                'score'    => 3,
                'detail'   => "{$pct}% reservasi partner ini dalam 30 hari terakhir adalah same-day booking.",
            ]];
        }
        return [];
    }

    private function checkUnusualHours(Reservation $reservation): array
    {
        $hour = (int) $reservation->created_at->format('H');
        if ($hour >= 0 && $hour < 5) {
            return [[
                'type'     => 'UNUSUAL_HOURS',
                'severity' => 'WARNING',
                'score'    => 3,
                'detail'   => "Reservasi dibuat jam {$reservation->created_at->format('H:i')} (jam operasional tidak wajar).",
            ]];
        }
        return [];
    }

    // ── Layer 2: Spatial ──────────────────────────────────────────────────────

    private function checkDangerZone(Reservation $reservation): array
    {
        if (!$reservation->latitude || !$reservation->longitude) return [];
        if ($reservation->reservation_type !== 'PARTNER') return [];

        if ($reservation->is_danger_zone) {
            $distance = round($this->dangerZone->distanceFromCenter(
                $reservation->latitude,
                $reservation->longitude
            ));
            $radius = Setting::get('danger_zone_radius_meters', 500);
            return [[
                'type'     => 'DANGER_ZONE',
                'severity' => 'CRITICAL',
                'score'    => 10,
                'detail'   => "Reservasi dibuat dari dalam danger zone ({$distance}m dari TSB, radius {$radius}m).",
            ]];
        }
        return [];
    }

    private function checkStationaryPattern(Reservation $reservation): array
    {
        if (!$reservation->partner_id || !$reservation->latitude || !$reservation->longitude) return [];

        // Count reservations from within 50m radius of this submission
        $nearby = Reservation::where('partner_id', $reservation->partner_id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('id', '!=', $reservation->id)
            ->get(['latitude', 'longitude'])
            ->filter(fn($r) => $this->dangerZone->haversineDistance(
                $r->latitude, $r->longitude,
                $reservation->latitude, $reservation->longitude
            ) <= 50)
            ->count();

        if ($nearby >= 5) {
            return [[
                'type'     => 'STATIONARY_PATTERN',
                'severity' => 'CRITICAL',
                'score'    => 10,
                'detail'   => "{$nearby} reservasi sebelumnya dikirim dari titik yang sama (radius 50m) — kemungkinan intercept di satu lokasi.",
            ]];
        }
        return [];
    }

    private function checkImpossibleTravel(Reservation $reservation): array
    {
        if (!$reservation->partner_id || !$reservation->latitude || !$reservation->longitude) return [];

        // Find previous reservation by this partner within last 30 mins
        $prev = Reservation::where('partner_id', $reservation->partner_id)
            ->whereNotNull('latitude')
            ->where('id', '!=', $reservation->id)
            ->where('created_at', '>=', $reservation->created_at->subMinutes(30))
            ->latest()
            ->first();

        if ($prev && $prev->latitude && $prev->longitude) {
            $dist = $this->dangerZone->haversineDistance(
                $reservation->latitude, $reservation->longitude,
                $prev->latitude, $prev->longitude
            );
            if ($dist > 50000) { // 50km
                $km = round($dist / 1000, 1);
                return [[
                    'type'     => 'IMPOSSIBLE_TRAVEL',
                    'severity' => 'CRITICAL',
                    'score'    => 10,
                    'detail'   => "2 reservasi dari lokasi berbeda {$km}km dalam < 30 menit — kemungkinan GPS spoofing atau akun sharing.",
                ]];
            }
        }
        return [];
    }

    // ── Layer 3: Behavioral ───────────────────────────────────────────────────

    private function checkHighOnTheSpotRatio(Reservation $reservation): array
    {
        if (!$reservation->partner_id) return [];

        $total = Reservation::where('partner_id', $reservation->partner_id)->count();
        if ($total < 10) return [];

        $ots = Reservation::where('partner_id', $reservation->partner_id)
            ->where('payment_method', 'ON_THE_SPOT')->count();

        if (($ots / $total) > 0.80) {
            $pct = round(($ots / $total) * 100);
            return [[
                'type'     => 'HIGH_ONTHESPOT_RATIO',
                'severity' => 'WARNING',
                'score'    => 3,
                'detail'   => "{$pct}% reservasi partner ini menggunakan ON THE SPOT (>80% threshold).",
            ]];
        }
        return [];
    }

    private function checkCommissionAnomaly(Reservation $reservation): array
    {
        if (!$reservation->partner_id) return [];
        if ($reservation->payment_method !== 'TRANSFER_GROSS') return [];
        if (!$reservation->is_danger_zone) return [];

        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $sameDayCount = Reservation::where('partner_id', $reservation->partner_id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->whereRaw('DATE(visit_date) = DATE(created_at)')
            ->count();
        $total = Reservation::where('partner_id', $reservation->partner_id)
            ->where('created_at', '>=', $thirtyDaysAgo)->count();

        if ($total > 5 && $sameDayCount / $total > 0.50) {
            return [[
                'type'     => 'COMMISSION_ANOMALY',
                'severity' => 'CRITICAL',
                'score'    => 10,
                'detail'   => "TRANSFER_GROSS + same-day pattern + danger zone — pola maximize komisi tanpa effort akuisisi.",
            ]];
        }
        return [];
    }

    private function checkVolumeSpike(Reservation $reservation): array
    {
        if (!$reservation->partner_id) return [];

        $thisMonth = Reservation::where('partner_id', $reservation->partner_id)
            ->whereMonth('created_at', now()->month)
            ->count();
        $avg30 = Reservation::where('partner_id', $reservation->partner_id)
            ->where('created_at', '>=', now()->subDays(60))
            ->count() / 2; // 2 months average

        if ($avg30 > 5 && $thisMonth > $avg30 * 2) {
            return [[
                'type'     => 'VOLUME_SPIKE',
                'severity' => 'WARNING',
                'score'    => 3,
                'detail'   => "Volume reservasi bulan ini ({$thisMonth}) > 200% rata-rata 30 hari (avg: " . round($avg30) . ").",
            ]];
        }
        return [];
    }

    // ── Layer 4: Identity ─────────────────────────────────────────────────────

    private function checkDummyPartnerSuspect(Reservation $reservation): array
    {
        if (!$reservation->partner_id) return [];
        $partner = $reservation->partner;
        if (!$partner) return [];

        $daysSinceCreated = Carbon::parse($partner->created_at)->diffInDays(now());
        if ($daysSinceCreated < 30) {
            $count = Reservation::where('partner_id', $reservation->partner_id)->count();
            if ($count > 10) {
                return [[
                    'type'     => 'DUMMY_PARTNER_SUSPECT',
                    'severity' => 'CRITICAL',
                    'score'    => 10,
                    'detail'   => "Partner baru ({$daysSinceCreated} hari) sudah memiliki {$count} reservasi — pola mencurigakan.",
                ]];
            }
        }
        return [];
    }

    // ── Layer 5: Customer Verification ────────────────────────────────────────

    private function checkCustomerWalkInClaim(Reservation $reservation): array
    {
        if ($reservation->customer_origin !== 'WALK_IN') return [];
        if ($reservation->payment_method !== 'TRANSFER_GROSS') return [];

        return [[
            'type'     => 'CUSTOMER_WALK_IN_CLAIM',
            'severity' => 'CRITICAL',
            'score'    => 10,
            'detail'   => "Customer mengklaim datang sendiri (WALK_IN) tapi partner mengambil komisi via TRANSFER_GROSS.",
        ]];
    }

    // ── Save & Score ──────────────────────────────────────────────────────────

    private function saveAnomalies(Reservation $reservation, array $anomalies): void
    {
        foreach ($anomalies as $a) {
            ReservationAnomaly::firstOrCreate(
                ['reservation_id' => $reservation->id, 'anomaly_type' => $a['type']],
                [
                    'severity'     => $a['severity'],
                    'detail'       => $a['detail'],
                    'score_impact' => $a['score'],
                    'is_resolved'  => false,
                    'created_at'   => now(),
                ]
            );
        }
    }

    private function updateFraudScore(Partner $partner): void
    {
        $score = ReservationAnomaly::whereHas('reservation', fn($q) => $q->where('partner_id', $partner->id))
            ->where('is_resolved', false)
            ->sum('score_impact');

        $partner->update(['fraud_score' => max(0, (int) $score)]);
    }

    private function applyAutoActions(Partner $partner): void
    {
        $score = (int) $partner->fresh()->fraud_score;

        if ($score > 50 && !$partner->reservation_suspended) {
            $partner->update([
                'reservation_suspended'        => true,
                'reservation_suspended_reason' => "Auto-suspended: fraud score {$score} melebihi threshold 50.",
            ]);
        }
    }
}
