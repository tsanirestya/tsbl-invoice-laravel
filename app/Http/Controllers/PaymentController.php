<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceLog;
use App\Models\Payment;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('partner')
            ->where('is_finalized', true)
            ->orderByDesc('due_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhereHas('partner', fn($p) => $p->where('nama_partner', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->paginate(25)->withQueryString();
        $partners = Partner::where('is_active', 1)->orderBy('nama_partner')->get(['id', 'nama_partner']);

        $summary = [
            'total_outstanding' => Invoice::where('is_finalized', true)
                ->whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])
                ->sum('grand_total'),
            'overdue_count' => Invoice::where('is_finalized', true)
                ->where('payment_status', 'OVERDUE')
                ->count(),
            'partial_count' => Invoice::where('is_finalized', true)
                ->where('payment_status', 'PARTIAL')
                ->count(),
        ];

        return view('payments.index', compact('invoices', 'partners', 'summary'));
    }

    public function store(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'reference_no'   => 'nullable|string|max:100',
            'proof_file'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes'          => 'nullable|string',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')
                ->store("payments/{$invoice->id}", 'public');
        }

        $payment = Payment::create([
            'invoice_id'     => $invoice->id,
            'amount'         => $request->amount,
            'payment_date'   => $request->payment_date,
            'payment_method' => $request->payment_method,
            'reference_no'   => $request->reference_no,
            'proof_file'     => $proofPath,
            'notes'          => $request->notes,
            'created_by'     => auth()->id(),
            'created_at'     => now(),
        ]);

        $invoice->recalcStatus();

        InvoiceLog::create([
            'invoice_id'  => $invoice->id,
            'action'      => 'PAYMENT_ADDED',
            'description' => "Pembayaran Rp " . number_format($payment->amount, 0, ',', '.') .
                             " ditambahkan" . ($payment->reference_no ? " — Ref: {$payment->reference_no}" : ''),
            'new_value'   => (string) $payment->amount,
            'created_by'  => auth()->id(),
            'created_at'  => now(),
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function destroy(Invoice $invoice, Payment $payment)
    {
        if ($payment->invoice_id !== $invoice->id) {
            abort(404);
        }

        if ($payment->proof_file) {
            Storage::disk('public')->delete($payment->proof_file);
        }

        $amount = $payment->amount;
        $payment->delete();

        $invoice->recalcStatus();

        InvoiceLog::create([
            'invoice_id'  => $invoice->id,
            'action'      => 'PAYMENT_DELETED',
            'description' => "Pembayaran Rp " . number_format($amount, 0, ',', '.') . " dihapus",
            'old_value'   => (string) $amount,
            'created_by'  => auth()->id(),
            'created_at'  => now(),
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Pembayaran berhasil dihapus.');
    }
}
