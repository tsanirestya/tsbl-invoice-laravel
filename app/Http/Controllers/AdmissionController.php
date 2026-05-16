<?php

namespace App\Http\Controllers;

use App\Models\DailyQrCode;
use App\Models\Reservation;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdmissionController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today();

        $confirmedToday = Reservation::whereDate('visit_date', $today)
            ->where('status', 'CONFIRMED')
            ->count();

        $redeemedToday = Reservation::whereDate('visit_date', $today)
            ->where('status', 'REDEEMED')
            ->count();

        $matchToday = Reservation::whereDate('visit_date', $today)
            ->where('status', 'REDEEMED')
            ->where('transaction_match', 'MATCH')
            ->count();

        $mismatchToday = Reservation::whereDate('visit_date', $today)
            ->where('status', 'REDEEMED')
            ->where('transaction_match', 'MISMATCH')
            ->count();

        $totalPaxIn = Reservation::whereDate('visit_date', $today)
            ->where('status', 'REDEEMED')
            ->selectRaw('COALESCE(SUM(pax_adults), 0) + COALESCE(SUM(pax_kids), 0) as total_pax')
            ->value('total_pax') ?? 0;

        $recentActivity = Reservation::with(['redeemer'])
            ->where('status', 'REDEEMED')
            ->whereDate('redeemed_at', $today)
            ->orderByDesc('redeemed_at')
            ->limit(10)
            ->get();

        return view('admission.dashboard', compact(
            'confirmedToday', 'redeemedToday', 'matchToday', 'mismatchToday',
            'totalPaxIn', 'recentActivity', 'today'
        ));
    }

    public function scanPage()
    {
        return view('admission.scan');
    }

    public function lookup(Request $request)
    {
        $request->validate(['reservation_no' => 'required|string']);

        $reservation = Reservation::with(['items.product', 'redeemer'])
            ->where('reservation_no', strtoupper(trim($request->reservation_no)))
            ->first();

        if (!$reservation) {
            return response()->json(['found' => false, 'message' => 'Reservasi tidak ditemukan.']);
        }

        $toleranceDays = (int) Setting::get('admission_visit_date_tolerance_days', 0);
        $visitDate     = $reservation->visit_date;
        $today         = Carbon::today();
        $withinRange   = $today->between(
            $visitDate->copy()->subDays($toleranceDays),
            $visitDate->copy()->addDays($toleranceDays)
        );

        return response()->json([
            'found'          => true,
            'reservation_no' => $reservation->reservation_no,
            'guest_name'     => $reservation->guest_name,
            'guest_country'  => $reservation->guest_country,
            'visit_date'     => $reservation->visit_date->format('d M Y'),
            'reservation_type' => $reservation->reservation_type,
            'status'         => $reservation->status,
            'status_badge'   => $reservation->statusBadge(),
            'pax_adults'     => $reservation->pax_adults,
            'pax_kids'       => $reservation->pax_kids,
            'pax_babies'     => $reservation->pax_babies,
            'items'          => $reservation->items->map(fn($item) => [
                'product_name' => $item->product_name,
                'qty'          => $item->qty,
                'amount'       => $item->amount,
            ]),
            'within_date_range' => $withinRange,
            'tolerance_days'    => $toleranceDays,
            'redeemed_at'       => $reservation->redeemed_at?->format('d M Y H:i'),
            'redeemed_by_name'  => $reservation->redeemer?->full_name,
            'transaction_match' => $reservation->transaction_match,
        ]);
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'reservation_no'    => 'required|string',
            'transaction_match' => 'required|in:MATCH,MISMATCH',
            'transaction_notes' => 'required_if:transaction_match,MISMATCH|nullable|string|max:1000',
        ]);

        $reservation = Reservation::where('reservation_no', strtoupper(trim($request->reservation_no)))->first();

        if (!$reservation) {
            return back()->with('error', 'Reservasi tidak ditemukan.');
        }

        if ($reservation->status !== 'CONFIRMED') {
            return back()->with('error', "Reservasi status '{$reservation->status}' tidak bisa di-redeem. Hanya CONFIRMED yang bisa di-redeem.");
        }

        $toleranceDays = (int) Setting::get('admission_visit_date_tolerance_days', 0);
        $visitDate     = $reservation->visit_date;
        $today         = Carbon::today();
        $withinRange   = $today->between(
            $visitDate->copy()->subDays($toleranceDays),
            $visitDate->copy()->addDays($toleranceDays)
        );

        if (!$withinRange) {
            $rangeText = $toleranceDays > 0
                ? "H-{$toleranceDays} sampai H+{$toleranceDays} dari visit date ({$visitDate->format('d M Y')})"
                : "tepat pada visit date ({$visitDate->format('d M Y')})";
            return back()->with('error', "Visit date tidak sesuai. Redeem hanya bisa dilakukan {$rangeText}.");
        }

        $reservation->update([
            'status'            => 'REDEEMED',
            'redeemed_at'       => now(),
            'redeemed_by'       => Auth::id(),
            'transaction_match' => $request->transaction_match,
            'transaction_notes' => $request->transaction_notes,
        ]);

        return redirect()->route('admission.scan')
            ->with('success', "Reservasi {$reservation->reservation_no} berhasil di-redeem sebagai {$request->transaction_match}.")
            ->with('redeemed_no', $reservation->reservation_no);
    }

    public function qrDisplay()
    {
        $todayQr = DailyQrCode::where('date', now()->toDateString())
            ->where('is_active', true)
            ->first();

        $qrUrl   = $todayQr ? route('self-service.scan', $todayQr->token) : null;
        $logoPath = Setting::get('logo_path');
        $logoUrl  = $logoPath ? asset($logoPath) : null;

        return view('admission.qr-display', compact('todayQr', 'qrUrl', 'logoUrl'));
    }

    public function history(Request $request)
    {
        $date  = $request->date ? Carbon::parse($request->date) : Carbon::today();
        $match = $request->match; // MATCH | MISMATCH | null (all)

        $query = Reservation::with(['redeemer'])
            ->where('status', 'REDEEMED')
            ->whereDate('redeemed_at', $date);

        if ($match && in_array($match, ['MATCH', 'MISMATCH'])) {
            $query->where('transaction_match', $match);
        }

        $reservations = $query->orderByDesc('redeemed_at')->paginate(30)->withQueryString();

        return view('admission.history', compact('reservations', 'date', 'match'));
    }
}
