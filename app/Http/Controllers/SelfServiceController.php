<?php

namespace App\Http\Controllers;

use App\Models\DailyQrCode;
use App\Models\Reservation;
use App\Services\AnomalyDetectionService;
use App\Services\BookingPassService;
use App\Services\DangerZoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SelfServiceController extends Controller
{
    public function __construct(
        private DangerZoneService       $dangerZone,
        private AnomalyDetectionService $anomalyDetection,
        private BookingPassService      $bookingPass,
    ) {}

    /** Public: Landing from QR scan (validates today's token) */
    public function scan(string $token)
    {
        if (!\App\Models\Setting::get('qr_self_service_enabled', '1')) {
            abort(403, 'Self-service QR tidak aktif saat ini.');
        }

        $qr = DailyQrCode::where('token', $token)->where('is_active', true)->first();
        if (!$qr || !$qr->isValidToday()) {
            abort(403, 'QR Code sudah tidak berlaku. Scan QR terbaru yang terpasang di lokasi.');
        }

        $hotelPartners = \App\Models\Partner::where('partner_type', 'HOTEL')->where('is_active', true)->orderBy('nama_partner')->get(['id', 'nama_partner']);

        return view('self-service.form', compact('qr', 'hotelPartners', 'token'));
    }

    public function store(Request $request, string $token)
    {
        $qr = DailyQrCode::where('token', $token)->where('is_active', true)->first();
        if (!$qr || !$qr->isValidToday()) {
            abort(403, 'QR Code sudah tidak berlaku.');
        }

        $validated = $request->validate([
            'guest_name'     => 'required|string|max:255',
            'guest_country'  => 'nullable|string|max:100',
            'pax_adults'     => 'required|integer|min:1|max:999',
            'pax_kids'       => 'required|integer|min:0|max:999',
            'partner_id'     => 'nullable|exists:partners,id',
            'visit_date'     => 'required|date',
            'latitude'       => 'required|numeric',
            'longitude'      => 'required|numeric',
            'location_name'  => 'nullable|string|max:255',
            'room_key_photo' => 'required|image|max:5120',
            'key_number'     => 'required|string|max:100',
        ]);

        $photoPath = $request->file('room_key_photo')->store('self-service-photos', 'public');

        $reservation = DB::transaction(function () use ($validated, $request, $token, $photoPath) {
            $isDangerZone = $this->dangerZone->isInDangerZone(
                (float) $validated['latitude'],
                (float) $validated['longitude']
            );

            $partnerId   = $validated['partner_id'] ?? null;
            $partnerName = $partnerId
                ? \App\Models\Partner::find($partnerId)?->nama_partner
                : null;

            $reservation = Reservation::create([
                'reservation_no'     => Reservation::generateReservationNo(),
                'partner_id'         => $partnerId,
                'guest_name'         => $validated['guest_name'],
                'guest_country'      => $validated['guest_country'] ?? null,
                'pax_adults'         => $validated['pax_adults'],
                'pax_kids'           => $validated['pax_kids'],
                'visit_date'         => $validated['visit_date'] ?? now()->format('Y-m-d'),
                'status'             => 'CONFIRMED',
                'reservation_type'   => 'SELF_SERVICE',
                'payment_method'     => 'ON_THE_SPOT',
                'customer_origin'    => 'HOTEL',
                'partner_name_input' => $partnerName,
                'is_danger_zone'     => $isDangerZone,
                'room_key_photo'     => $photoPath,
                'key_number'         => $validated['key_number'] ?? null,
                'latitude'           => $validated['latitude'],
                'longitude'          => $validated['longitude'],
                'location_name'      => $validated['location_name'] ?? null,
                'ip_address'         => $request->ip(),
                'user_agent'         => $request->userAgent(),
                'qr_token'           => $token,
                'created_by'         => null,
            ]);

            // Items akan diisi otomatis saat data DSI masuk dengan booking_pass_no yang sama
            // Generate booking pass
            $this->bookingPass->generate($reservation->fresh());

            return $reservation;
        });

        return redirect()->route('self-service.success', [$token, $reservation->reservation_no]);
    }

    public function success(string $token, string $reservationNo)
    {
        $reservation = Reservation::with('items')
            ->where('reservation_no', $reservationNo)
            ->firstOrFail();

        return view('self-service.success', compact('reservation', 'token'));
    }

    public function bookingPassDownload(string $token, string $reservationNo)
    {
        $reservation = Reservation::where('reservation_no', $reservationNo)->firstOrFail();

        if (!$reservation->booking_pass_file) {
            $this->bookingPass->generate($reservation);
            $reservation->refresh();
        }

        $path = storage_path('app/public/' . $reservation->booking_pass_file);
        if (!file_exists($path)) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        return response()->download($path, $reservation->reservation_no . '-booking-pass.pdf');
    }

    public function bookingPassView(string $token, string $reservationNo)
    {
        $reservation = Reservation::where('reservation_no', $reservationNo)->firstOrFail();

        if (!$reservation->booking_pass_file) {
            $this->bookingPass->generate($reservation);
            $reservation->refresh();
        }

        $path = storage_path('app/public/' . $reservation->booking_pass_file);
        if (!file_exists($path)) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $reservation->reservation_no . '-booking-pass.pdf"',
        ]);
    }

    // ── Admin: Generate Daily QR ──────────────────────────────────────────────

    public function generateQr(Request $request)
    {
        // Deactivate old QRs
        DailyQrCode::where('date', '<', now()->toDateString())->update(['is_active' => false]);

        $existing = DailyQrCode::where('date', now()->toDateString())->first();
        if ($existing) {
            return back()->with('info', 'QR untuk hari ini sudah ada: ' . route('self-service.scan', $existing->token));
        }

        $qr = DailyQrCode::create([
            'date'         => now()->toDateString(),
            'token'        => Str::random(48),
            'is_active'    => true,
            'generated_by' => auth()->id(),
        ]);

        return back()->with('success', 'QR harian berhasil dibuat. Token: ' . $qr->token);
    }

    public function qrIndex()
    {
        $qrs = DailyQrCode::with('generatedBy')->latest()->paginate(30);
        return view('self-service.qr-admin', compact('qrs'));
    }
}
