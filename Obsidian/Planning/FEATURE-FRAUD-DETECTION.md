---
title: Feature Planning — Anomaly & Fraud Detection
status: APPROVED
created: 2026-05-13
phase: 10f
---

# Feature Planning — Anomaly & Fraud Detection (Phase 10f)

> Kembali ke: [[FEATURE-RESERVATION-SYSTEM]]
> Related: [[FEATURE-BOOKING-PASS]]

---

## Latar Belakang

### Masalah Utama
Partner nakal atau dummy partner (bisa jadi karyawan sendiri) mencegat customer FIT (Free Independent Traveler) di depan Trans Studio Bali. Modusnya:
1. Customer memang sudah mau datang sendiri (bukan referral partner)
2. Partner nakal cegat di area parkir/pintu masuk
3. Tawarkan "diskon 10% pakai booking pass"
4. Customer setuju (merasa untung)
5. Partner input reservasi ON THE SPOT
6. Partner ambil komisi padahal ZERO effort akuisisi
7. **TSBL rugi: bayar komisi untuk customer yang sudah mau datang**

### Masalah Sekunder
- Karyawan buat dummy partner atas nama sendiri/keluarga
- GPS spoofing untuk menghindari danger zone detection
- Partner share token link ke orang lain
- Volume reservasi tidak wajar (bulk booking palsu)

---

## Keputusan Desain

| Keputusan | Pilihan | Alasan |
|---|---|---|
| Block atau flag? | **Flag only** (tidak block transaksi) | Transaksi tetap jalan, review belakangan. Mencegah false positive ganggu operasional |
| Fraud scoring | Point-based per partner | Akumulatif, bisa auto-escalate |
| Commission hold | Auto-hold saat risk HIGH/CRITICAL | Komisi tidak hilang, hanya di-hold sampai review |
| Partner suspend | Auto-suspend saat score > 50 | Token dinonaktifkan, perlu ADMIN re-enable |
| Employee cross-check | Separate table | Bisa dijalankan periodik, tidak block partner creation |

---

## 5-Layer Fraud Detection Framework

### Layer 1: Temporal Analysis (Waktu)

| Rule | Trigger | Severity | Logic |
|---|---|---|---|
| `LAST_MINUTE_BOOKING` | Reservasi < 2 jam sebelum visit_date DAN dari danger zone | CRITICAL (+10) | Partner legit reservasi H-1 minimum, bukan di depan pintu |
| `SAME_DAY_PATTERN` | Partner > 60% reservasi same-day dalam 30 hari terakhir | WARNING (+3) | Partner legit punya advance booking pattern |
| `UNUSUAL_HOURS` | Reservasi dibuat jam 00:00-05:00 | WARNING (+3) | Jam operasional tidak wajar |

### Layer 2: Spatial Analysis (Lokasi)

| Rule | Trigger | Severity | Logic |
|---|---|---|---|
| `DANGER_ZONE` | GPS dalam radius danger zone (configurable, default 500m dari TSB) DAN reservation_type = PARTNER | CRITICAL (+10) | Partner harusnya reservasi dari luar area TSB |
| `STATIONARY_PATTERN` | Partner > 5 reservasi dari radius 50m yang sama (bukan kantor/hotel partner) | CRITICAL (+10) | Nongkrong di satu titik = intercept |
| `IMPOSSIBLE_TRAVEL` | 2 reservasi partner sama, lokasi > 50km, interval < 30 menit | CRITICAL (+10) | GPS spoofing atau akun sharing |

### Layer 3: Behavioral Analysis (Pola)

| Rule | Trigger | Severity | Logic |
|---|---|---|---|
| `NO_REPEAT_GUEST` | Partner 0% repeat guest setelah > 20 reservasi | WARNING (+3) | Random intercept, bukan relationship |
| `HIGH_ONTHESPOT_RATIO` | Partner > 80% payment ON_THE_SPOT | WARNING (+3) | Partner legit biasanya transfer |
| `COMMISSION_ANOMALY` | Selalu TRANSFER_GROSS + match pattern lain (same-day + danger zone) | CRITICAL (+10) | Maximize komisi tanpa effort |
| `VOLUME_SPIKE` | Volume > 200% dari 30-day average, tanpa event/musim | WARNING (+3) | Aktivitas tidak wajar |

### Layer 4: Identity Analysis (Siapa)

| Rule | Trigger | Severity | Logic |
|---|---|---|---|
| `DUMMY_PARTNER_SUSPECT` | Partner baru (< 30 hari) + langsung volume tinggi | CRITICAL (+10) | Akun baru langsung produktif = suspicious |
| `EMPLOYEE_LINKED` | Data partner match dengan data karyawan (phone/email/bank/address) | CRITICAL (+10) | Karyawan bikin partner dummy |

### Layer 5: Customer Verification

| Rule | Trigger | Severity | Logic |
|---|---|---|---|
| `CUSTOMER_WALK_IN_CLAIM` | customer_origin = WALK_IN tapi partner claim komisi (TRANSFER_GROSS) | CRITICAL (+10) | Customer bilang datang sendiri, partner mau ambil komisi |
| `SPOT_CHECK_REQUIRED` | Random 10% dari reservasi ON_THE_SPOT | WARNING (+3) | Verifikasi manual sampel |

---

## Struktur Data

### Tabel `reservation_anomalies`
```
id                    bigint PK
reservation_id        bigint FK CASCADE
anomaly_type          varchar(50)           -- DANGER_ZONE, LAST_MINUTE_BOOKING, dll
severity              enum(WARNING, CRITICAL)
detail                text                  -- deskripsi human-readable
score_impact          int                   -- +3 atau +10
is_resolved           boolean default false
resolved_by           bigint FK nullable
resolved_at           datetime nullable
resolution_notes      text nullable
resolution_type       enum(CLEARED, CONFIRMED_FRAUD, FALSE_POSITIVE) nullable
created_at
```

### Tabel `employee_partner_checks`
```
id                    bigint PK
partner_id            bigint FK CASCADE
user_id               bigint FK CASCADE     -- karyawan yang match
match_type            enum(PHONE, EMAIL, BANK_ACCOUNT, ADDRESS, NAME)
match_detail          text                  -- detail kesamaan
is_reviewed           boolean default false
reviewed_by           bigint FK nullable
reviewed_at           datetime nullable
review_notes          text nullable
created_at
```

### Perubahan di `partners` (sudah di FEATURE-RESERVATION-SYSTEM)
```
fraud_score                  int default 0
reservation_suspended        boolean default false
reservation_suspended_reason text nullable
```

### Perubahan di `reservation_payments` (sudah di FEATURE-RESERVATION-SYSTEM)
```
is_commission_held           boolean default false
commission_hold_reason       text nullable
commission_released_by       bigint FK nullable
commission_released_at       datetime nullable
```

---

## Fraud Score System

### Scoring
```
Setiap anomaly menambah score ke partner:
  CRITICAL anomaly = +10 poin
  WARNING anomaly  = +3 poin

Setiap anomaly di-resolve:
  CLEARED / FALSE_POSITIVE = -5 poin (min 0)
  CONFIRMED_FRAUD = score tetap (tidak berkurang)
```

### Risk Levels & Auto-Actions

| Score | Level | Color | Auto-Action |
|---|---|---|---|
| 0-10 | LOW | Hijau | Normal operation |
| 11-30 | MEDIUM | Kuning | Semua reservasi partner di-flag untuk review |
| 31-50 | HIGH | Oranye | **Commission hold** otomatis — komisi tidak dibayar sampai review |
| 51+ | CRITICAL | Merah | **Auto-suspend** reservation token + notifikasi ADMIN |

### Commission Hold Mechanism
```
1. Partner risk = HIGH atau CRITICAL
2. Reservasi baru tetap bisa dibuat (tidak block)
3. ReservationPayment.is_commission_held = true
4. Muncul di "Commission Review" queue
5. ADMIN/FINANCE review:
   - Release: komisi dibayar, is_commission_held = false
   - Revoke: komisi dibatalkan, commission_amount = 0
```

---

## AnomalyDetectionService

```php
class AnomalyDetectionService
{
    public function check(Reservation $reservation): array
    {
        $anomalies = [];

        // Layer 1: Temporal
        $anomalies = array_merge($anomalies, $this->checkLastMinuteBooking($reservation));
        $anomalies = array_merge($anomalies, $this->checkSameDayPattern($reservation));
        $anomalies = array_merge($anomalies, $this->checkUnusualHours($reservation));

        // Layer 2: Spatial
        $anomalies = array_merge($anomalies, $this->checkDangerZone($reservation));
        $anomalies = array_merge($anomalies, $this->checkStationaryPattern($reservation));
        $anomalies = array_merge($anomalies, $this->checkImpossibleTravel($reservation));

        // Layer 3: Behavioral
        $anomalies = array_merge($anomalies, $this->checkNoRepeatGuest($reservation));
        $anomalies = array_merge($anomalies, $this->checkHighOnTheSpotRatio($reservation));
        $anomalies = array_merge($anomalies, $this->checkCommissionAnomaly($reservation));
        $anomalies = array_merge($anomalies, $this->checkVolumeSpike($reservation));

        // Layer 4: Identity (run periodically, not per-reservation)
        // Layer 5: Customer verification (set flags)

        // Save anomalies & update partner fraud score
        $this->saveAnomalies($reservation, $anomalies);
        $this->updateFraudScore($reservation->partner);
        $this->applyAutoActions($reservation->partner);

        return $anomalies;
    }

    private function checkDangerZone(Reservation $reservation): array
    {
        if ($reservation->reservation_type !== 'PARTNER') return [];
        if (!$reservation->latitude || !$reservation->longitude) return [];

        $distance = $this->haversineDistance(
            $reservation->latitude,
            $reservation->longitude,
            Setting::get('danger_zone_latitude'),
            Setting::get('danger_zone_longitude')
        );

        $radius = Setting::get('danger_zone_radius_meters', 500);

        if ($distance <= $radius) {
            return [[
                'type' => 'DANGER_ZONE',
                'severity' => 'CRITICAL',
                'score' => 10,
                'detail' => "Reservasi dibuat dari dalam danger zone ({$distance}m dari TSB, radius {$radius}m)"
            ]];
        }
        return [];
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $R = 6371000; // Earth radius in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
    }

    private function applyAutoActions(Partner $partner): void
    {
        $score = $partner->fraud_score;

        if ($score > 50 && !$partner->reservation_suspended) {
            $partner->update([
                'reservation_suspended' => true,
                'reservation_suspended_reason' => "Auto-suspended: fraud score {$score} exceeded threshold 50"
            ]);
            // TODO: notify ADMIN
        }
    }
}
```

---

## Employee-Partner Cross Check

### Periodik Check (Artisan Command / Web Route)
```php
class EmployeePartnerCheckService
{
    public function runFullCheck(): array
    {
        $partners = Partner::all();
        $users = User::all();
        $matches = [];

        foreach ($partners as $partner) {
            foreach ($users as $user) {
                // Phone match
                if ($this->similarPhone($partner->pic_partner_phone, $user->phone)) {
                    $matches[] = [...];
                }
                // Email match
                if ($partner->pic_partner_email === $user->email) {
                    $matches[] = [...];
                }
                // Bank account match
                if ($this->similarBankAccount($partner, $user)) {
                    $matches[] = [...];
                }
                // Name similarity (fuzzy)
                if ($this->similarName($partner->pic_partner, $user->full_name)) {
                    $matches[] = [...];
                }
            }
        }

        return $matches;
    }
}
```

---

## GPS + IP Cross-Check

### Anti-Spoofing
```
Saat submit reservasi:
1. Capture GPS koordinat (Geolocation API)
2. Capture IP address (server-side)
3. Lookup IP geolocation (free: ip-api.com atau DB lokal)
4. Bandingkan jarak GPS vs IP location
5. Jika > 100km -> flag LOCATION_SPOOFING_SUSPECT
```

---

## Controllers & Routes

### ReservationAnomalyController
```
GET  /anomalies                           -> index (ADMIN/FINANCE) — dashboard
GET  /anomalies/{id}                      -> show (ADMIN/FINANCE) — detail
POST /anomalies/{id}/resolve              -> resolve (ADMIN/FINANCE)
GET  /anomalies/export                    -> export Excel/PDF (ADMIN/FINANCE)
```

### Commission Review (di ReservationController atau dedicated)
```
GET  /commission-review                   -> index (ADMIN/FINANCE) — held commissions
POST /commission-review/{id}/release      -> release commission (ADMIN)
POST /commission-review/{id}/revoke       -> revoke commission (ADMIN)
```

### Employee Check (Admin only)
```
GET  /admin/employee-partner-checks       -> index (ADMIN) — results
POST /admin/employee-partner-checks/run   -> run check (ADMIN)
POST /admin/employee-partner-checks/{id}/review -> mark reviewed (ADMIN)
```

---

## Anomaly Dashboard Layout

```
+----------------------------------------------------------+
|  ANOMALY & FRAUD PREVENTION                    [Export]   |
|----------------------------------------------------------|
|  [CRITICAL: 12]  [WARNING: 34]  [PENDING: 18]  [RESOLVED: 156]
|----------------------------------------------------------|
|                                                           |
|  HIGH RISK PARTNERS                                       |
|  +--------+------------------+-------+--------+---------+ |
|  | Rank   | Partner          | Score | Level  | Action  | |
|  +--------+------------------+-------+--------+---------+ |
|  | 1      | Partner ABC      | 62    | CRIT   | [View]  | |
|  | 2      | Partner XYZ      | 45    | HIGH   | [View]  | |
|  | 3      | Partner DEF      | 22    | MEDIUM | [View]  | |
|  +--------+------------------+-------+--------+---------+ |
|                                                           |
|  RECENT ANOMALIES                   [Filter by type/date] |
|  +----+-------------------+---------+--------+----------+ |
|  | #  | Type              | Partner | Sev.   | Action   | |
|  +----+-------------------+---------+--------+----------+ |
|  | 1  | DANGER_ZONE       | ABC     | CRIT   | [Resolve]| |
|  | 2  | LAST_MINUTE       | ABC     | CRIT   | [Resolve]| |
|  | 3  | HIGH_ONTHESPOT    | XYZ     | WARN   | [Resolve]| |
|  +----+-------------------+---------+--------+----------+ |
|                                                           |
|  COMMISSION HOLD QUEUE                                    |
|  +----+-----------+---------+--------+-------------------+|
|  | #  | Res No    | Partner | Amount | Action            ||
|  +----+-----------+---------+--------+-------------------+|
|  | 1  | RES-..012 | ABC     | 500K   | [Release] [Revoke]||
|  +----+-----------+---------+--------+-------------------+|
|                                                           |
|  EMPLOYEE-PARTNER MATCHES              [Run Check]       |
|  +----+-----------+-----------+--------+---------+-------+|
|  | #  | Partner   | Employee  | Match  | Status  | Act   ||
|  +----+-----------+-----------+--------+---------+-------+|
|  | 1  | PT Dummy  | Budi S.   | Phone  | Pending | [Rev] ||
|  +----+-----------+-----------+--------+---------+-------+|
+----------------------------------------------------------+
```

---

## Implementasi Steps

```
Agent: Software Architect
File: agency-agents/engineering/engineering-software-architect.md
Reason: Rule engine design, detection pattern architecture, service design
```
```
Agent: Security Engineer
File: agency-agents/engineering/engineering-security-engineer.md
Reason: Anti-spoofing, IP validation, fraud prevention patterns
```

28. Migration `2026_05_13_100007_create_reservation_anomalies_table`
29. Migration `2026_05_13_100008_create_employee_partner_checks_table`
30. Model `ReservationAnomaly` + relationships
31. Model `EmployeePartnerCheck` + relationships
32. `AnomalyDetectionService` — 12 detection rules
33. `EmployeePartnerCheckService` — cross-reference logic
34. `DangerZoneService` — Haversine calculation
35. `ReservationAnomalyController` — dashboard + resolve + export
36. Commission review routes + controller methods
37. Views: `anomalies/index`, `anomalies/show`, `commission-review/index`
38. Employee-partner check admin page

---

## Fraud Report Export

### Excel Export Columns
```
Reservation No | Date | Partner | Guest | Type | Payment | Amount | Commission |
Anomaly Type | Severity | Score Impact | Status | Resolved By | Resolution |
GPS Lat/Lng | Location | Danger Zone? | IP Address | Device
```

### PDF Summary Report
```
- Periode
- Total reservasi
- Total anomaly (by type, by severity)
- Top 10 high-risk partners
- Commission held summary
- Employee-partner matches
- Trend chart (anomaly per minggu)
```

---

## QA Protocol

```
Agent: Evidence Collector
File: agency-agents/testing/testing-evidence-collector.md
Reason: Visual proof, edge case testing
```

### Test Cases
1. Partner reservasi dari dalam danger zone -> anomaly CRITICAL ter-flag
2. Partner reservasi same-day >60% -> anomaly WARNING ter-flag
3. Fraud score > 50 -> partner auto-suspended
4. Commission hold saat HIGH risk -> is_commission_held = true
5. Resolve anomaly -> fraud score berkurang
6. Employee-partner match phone -> ter-detect
7. GPS spoofing (lat/lng jauh dari IP) -> LOCATION_SPOOFING ter-flag
8. Customer origin WALK_IN + partner TRANSFER_GROSS -> CRITICAL flag
9. Export fraud report -> semua data ter-include
10. Commission release/revoke -> payment updated correctly
