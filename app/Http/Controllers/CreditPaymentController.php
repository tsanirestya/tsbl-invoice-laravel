<?php

namespace App\Http\Controllers;

use App\Models\CreditPayment;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\PartnerDeposit;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditPaymentController extends Controller
{
    public function index()
    {
        $batches = CreditPayment::with(['partner', 'creator'])
            ->withCount('invoicePayments')
            ->latest()
            ->paginate(20);

        return view('credit-payments.index', compact('batches'));
    }

    public function create()
    {
        $partners = Partner::where('is_active', true)
            ->where('limit_credit', '>', 0)
            ->orderBy('nama_partner')
            ->get(['id', 'nama_partner', 'limit_credit']);

        return view('credit-payments.create', compact('partners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'partner_id'     => 'required|exists:partners,id',
            'total_received' => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string|max:50',
            'reference_no'   => 'nullable|string|max:100',
            'proof_file'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes'          => 'nullable|string|max:1000',
            'allocations'    => 'required|array|min:1',
            'allocations.*'  => 'nullable|numeric|min:0',
        ]);

        $partner       = Partner::findOrFail($request->partner_id);
        $totalReceived = (float) $request->total_received;

        // Filter non-zero allocations
        $allocations = collect($request->allocations)
            ->filter(fn($a) => $a !== null && (float) $a > 0)
            ->map(fn($a) => (float) $a);

        if ($allocations->isEmpty()) {
            return back()->withInput()
                ->withErrors(['allocations' => 'Minimal 1 invoice harus memiliki nominal alokasi > 0.']);
        }

        // Verify invoices belong to partner and are outstanding
        $invoiceIds = $allocations->keys()->toArray();
        $invoices   = Invoice::whereIn('id', $invoiceIds)
            ->where('partner_id', $partner->id)
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])
            ->get()
            ->keyBy('id');

        // Validate each allocation ≤ remaining balance
        $errors = [];
        foreach ($allocations as $invoiceId => $amount) {
            if (!isset($invoices[$invoiceId])) {
                $errors[] = "Invoice #{$invoiceId} tidak ditemukan atau sudah lunas — alokasi dilewati.";
                $allocations->forget($invoiceId);
                continue;
            }
            $inv  = $invoices[$invoiceId];
            $sisa = (float) $inv->grand_total - (float) $inv->payments()->sum('amount');
            if ($amount > $sisa + 0.001) {
                return back()->withInput()
                    ->withErrors(["allocations.{$invoiceId}" => "Alokasi {$inv->invoice_no} (Rp " . number_format($amount, 0, ',', '.') . ") melebihi sisa tagihan (Rp " . number_format($sisa, 0, ',', '.') . ")."]);
            }
        }

        $totalAllocated  = $allocations->sum();
        $excessToDeposit = max(0, $totalReceived - $totalAllocated);

        if ($totalAllocated > $totalReceived + 0.001) {
            return back()->withInput()
                ->withErrors(['total_received' => 'Total alokasi melebihi total nominal diterima.']);
        }

        // Upload proof
        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store('credit-payment-proofs', 'public');
        }

        DB::transaction(function () use ($request, $partner, $allocations, $totalAllocated, $excessToDeposit, $proofPath, $invoices) {
            // 1. Create deposit TOPUP if excess
            $depositRecord = null;
            if ($excessToDeposit > 0) {
                $depositRecord = PartnerDeposit::create([
                    'partner_id'  => $partner->id,
                    'type'        => 'TOPUP',
                    'amount'      => $excessToDeposit,
                    'reference_no'=> null,
                    'notes'       => 'Sisa batch payment — otomatis',
                    'created_by'  => auth()->id(),
                    'created_at'  => now(),
                ]);
            }

            // 2. Create batch header
            $batch = CreditPayment::create([
                'partner_id'             => $partner->id,
                'batch_no'               => CreditPayment::generateBatchNo(),
                'payment_date'           => $request->payment_date,
                'total_received'         => $request->total_received,
                'total_allocated'        => $totalAllocated,
                'excess_to_deposit'      => $excessToDeposit,
                'deposit_transaction_id' => $depositRecord?->id,
                'payment_method'         => $request->payment_method,
                'reference_no'           => $request->reference_no,
                'proof_file'             => $proofPath,
                'notes'                  => $request->notes,
                'created_by'             => auth()->id(),
            ]);

            // 3. Create Payment records per invoice
            foreach ($allocations as $invoiceId => $amount) {
                if (!isset($invoices[$invoiceId])) continue;

                Payment::create([
                    'invoice_id'        => $invoiceId,
                    'amount'            => $amount,
                    'payment_date'      => $request->payment_date,
                    'payment_method'    => $request->payment_method,
                    'reference_no'      => $batch->batch_no,
                    'notes'             => "Batch {$batch->batch_no}",
                    'credit_payment_id' => $batch->id,
                    'created_by'        => auth()->id(),
                    'created_at'        => now(),
                ]);

                $invoices[$invoiceId]->recalcStatus();
            }

            // Update deposit notes with actual batch_no
            if ($depositRecord) {
                $depositRecord->update(['notes' => "Sisa batch payment {$batch->batch_no}"]);
            }
        });

        return redirect()->route('credit-payments.index')
            ->with('success', 'Batch pembayaran credit berhasil disimpan.');
    }

    public function show(CreditPayment $creditPayment)
    {
        $creditPayment->load([
            'partner',
            'creator',
            'voidedByUser',
            'voidRequestedBy',
            'depositTransaction',
            'invoicePayments.invoice',
        ]);

        return view('credit-payments.show', compact('creditPayment'));
    }

    public function destroy(CreditPayment $creditPayment, Request $request)
    {
        if ($creditPayment->is_voided) {
            return back()->with('error', 'Batch ini sudah dibatalkan sebelumnya.');
        }

        // If user is FINANCE, they can only request a void
        if (auth()->user()->isFinance() && !auth()->user()->isAdmin()) {
            if ($creditPayment->isVoidPending()) {
                return back()->with('error', 'Permintaan pembatalan sudah diajukan dan sedang menunggu persetujuan Admin.');
            }

            $request->validate([
                'void_reason' => 'required|string|max:500',
            ]);

            $creditPayment->update([
                'void_requested_at' => now(),
                'void_requested_by' => auth()->id(),
                'void_reason'       => $request->void_reason,
            ]);

            return back()->with('success', "Permintaan pembatalan untuk batch {$creditPayment->batch_no} telah dikirim ke Admin.");
        }

        // If user is ADMIN, they can execute the void immediately (or they are confirming a request)
        return $this->executeVoid($creditPayment);
    }

    public function confirmVoid(CreditPayment $creditPayment)
    {
        // This method is gated by role:ADMIN in routes
        return $this->executeVoid($creditPayment);
    }

    public function rejectVoid(CreditPayment $creditPayment)
    {
        // This method is gated by role:ADMIN in routes
        $creditPayment->update([
            'void_requested_at' => null,
            'void_requested_by' => null,
            'void_reason'       => null,
        ]);

        return back()->with('success', "Permintaan pembatalan untuk batch {$creditPayment->batch_no} telah ditolak.");
    }

    protected function executeVoid(CreditPayment $creditPayment)
    {
        if ($creditPayment->is_voided) {
            return back()->with('error', 'Batch ini sudah dibatalkan sebelumnya.');
        }

        // Guard: if excess deposit was already used (balance would go negative after removal)
        if ($creditPayment->excess_to_deposit > 0 && $creditPayment->deposit_transaction_id) {
            $currentBalance = $creditPayment->partner->depositBalance();
            if ($currentBalance < $creditPayment->excess_to_deposit) {
                return back()->with('error', 'Deposit dari batch ini sudah terpakai. Batch tidak bisa dibatalkan.');
            }
        }

        DB::transaction(function () use ($creditPayment) {
            // 1. Collect affected invoice IDs before deleting payments
            $invoiceIds = $creditPayment->invoicePayments()->pluck('invoice_id')->toArray();

            // 2. Mark voided first (audit trail)
            $creditPayment->update([
                'is_voided'         => true,
                'voided_at'         => now(),
                'voided_by'         => auth()->id(),
                'void_requested_at' => $creditPayment->void_requested_at ?? now(), // Keep original request time if exists
            ]);

            // 3. Delete Payment records linked to this batch
            $creditPayment->invoicePayments()->delete();

            // 4. Recalc status for each affected invoice
            Invoice::whereIn('id', $invoiceIds)->each(fn($inv) => $inv->recalcStatus());

            // 5. Remove the excess deposit record if any
            if ($creditPayment->deposit_transaction_id) {
                PartnerDeposit::find($creditPayment->deposit_transaction_id)?->delete();
                $creditPayment->update(['deposit_transaction_id' => null]);
            }
        });

        return redirect()->route('credit-payments.index')
            ->with('success', "Batch {$creditPayment->batch_no} berhasil dibatalkan.");
    }

    /** AJAX: outstanding invoices for a partner (for credit payment form) */
    public function outstandingInvoices(Partner $partner)
    {
        $invoices = Invoice::where('partner_id', $partner->id)
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])
            ->orderByRaw("CASE payment_status WHEN 'OVERDUE' THEN 0 WHEN 'PARTIAL' THEN 1 ELSE 2 END")
            ->orderBy('due_date')
            ->get();

        return response()->json($invoices->map(function ($inv) {
            $totalPaid = (float) $inv->payments()->sum('amount');
            $sisa      = max(0, (float) $inv->grand_total - $totalPaid);

            return [
                'id'           => $inv->id,
                'invoice_no'   => $inv->invoice_no,
                'invoice_date' => $inv->invoice_date?->format('d/m/Y'),
                'due_date'     => $inv->due_date?->format('d/m/Y'),
                'grand_total'  => (float) $inv->grand_total,
                'total_paid'   => $totalPaid,
                'sisa'         => $sisa,
                'status'       => $inv->payment_status,
            ];
        })->values());
    }
}
