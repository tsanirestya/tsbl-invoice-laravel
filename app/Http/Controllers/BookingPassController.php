<?php

namespace App\Http\Controllers;

use App\Models\BookingPassTemplate;
use App\Models\Partner;
use App\Models\Reservation;
use App\Services\BookingPassService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookingPassController extends Controller
{
    public function index()
    {
        $templates = BookingPassTemplate::with('partner', 'creator')->latest()->paginate(20);
        return view('booking-pass-templates.index', compact('templates'));
    }

    public function create()
    {
        $partners = Partner::where('is_active', true)->orderBy('nama_partner')->get(['id', 'nama_partner']);
        return view('booking-pass-templates.create', compact('partners'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_name' => 'required|string|max:255',
            'partner_id'    => 'nullable|exists:partners,id',
            'template_file' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'is_active'     => 'boolean',
            'qr_type'       => 'in:qr,barcode',
            'template_type' => 'nullable|in:self_service,internal,partner',
        ]);

        $path = null;
        if ($request->hasFile('template_file')) {
            $path = $request->file('template_file')->store('booking-pass-templates', 'public');
        }

        $template = BookingPassTemplate::create([
            'template_name' => $validated['template_name'],
            'partner_id'    => $validated['partner_id'] ?? null,
            'template_file' => $path,
            'is_active'     => $request->boolean('is_active', true),
            'qr_type'       => $request->input('qr_type', 'qr'),
            'template_type' => $request->input('template_type'),
            'created_by'    => auth()->id(),
        ]);

        return redirect()->route('booking-pass-templates.edit', $template)
            ->with('success', 'Template berhasil dibuat. Sekarang atur layout variabel di editor.');
    }

    public function edit(BookingPassTemplate $bookingPassTemplate)
    {
        $partners           = Partner::where('is_active', true)->orderBy('nama_partner')->get(['id', 'nama_partner']);
        $recentReservations = Reservation::latest()->limit(30)->get(['id', 'reservation_no', 'guest_name', 'visit_date']);
        return view('booking-pass-templates.edit', compact('bookingPassTemplate', 'partners', 'recentReservations'));
    }

    public function update(Request $request, BookingPassTemplate $bookingPassTemplate)
    {
        $request->validate([
            'template_name' => 'required|string|max:255',
            'partner_id'    => 'nullable|exists:partners,id',
            'is_active'     => 'boolean',
            'qr_type'       => 'in:qr,barcode',
            'template_type' => 'nullable|in:self_service,internal,partner',
        ]);

        $bookingPassTemplate->update([
            'template_name' => $request->template_name,
            'partner_id'    => $request->partner_id,
            'is_active'     => $request->boolean('is_active', true),
            'qr_type'       => $request->input('qr_type', 'qr'),
            'template_type' => $request->input('template_type'),
        ]);

        return redirect()->route('booking-pass-templates.edit', $bookingPassTemplate)
            ->with('success', 'Info template diperbarui.');
    }

    /** AJAX: Upload background image */
    public function uploadBackground(Request $request, BookingPassTemplate $bookingPassTemplate)
    {
        $request->validate([
            'template_file' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($bookingPassTemplate->template_file) {
            Storage::disk('public')->delete($bookingPassTemplate->template_file);
        }

        $path = $request->file('template_file')->store('booking-pass-templates', 'public');
        $bookingPassTemplate->update(['template_file' => $path]);

        return response()->json([
            'success' => true,
            'url'     => asset('storage/' . $path),
            'path'    => $path,
        ]);
    }

    /** AJAX: Save field_mapping from visual editor */
    public function updateFieldMapping(Request $request, BookingPassTemplate $bookingPassTemplate)
    {
        $request->validate([
            'field_mapping' => 'required|array',
        ]);

        $bookingPassTemplate->update(['field_mapping' => $request->field_mapping]);

        return response()->json(['success' => true]);
    }

    /** Preview PDF — dummy data, or real reservation if reservation_id supplied */
    public function previewPdf(Request $request, BookingPassTemplate $bookingPassTemplate)
    {
        $service = app(BookingPassService::class);

        if ($request->filled('reservation_id')) {
            $reservation = Reservation::findOrFail($request->reservation_id);
            $url = $service->previewWithReservation($bookingPassTemplate, $reservation);
        } else {
            $url = $service->previewWithTemplate($bookingPassTemplate);
        }

        return response()->json(['url' => $url]);
    }

    public function destroy(BookingPassTemplate $bookingPassTemplate)
    {
        if ($bookingPassTemplate->template_file) {
            Storage::disk('public')->delete($bookingPassTemplate->template_file);
        }
        $bookingPassTemplate->delete();

        return back()->with('success', 'Template dihapus.');
    }
}
