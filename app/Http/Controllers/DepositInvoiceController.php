<?php

namespace App\Http\Controllers;

use App\Helpers\Terbilang;
use App\Models\DepositInvoice;
use App\Models\Partner;
use App\Models\PartnerDeposit;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DepositInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = DepositInvoice::with(['partner', 'creator'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('partner', fn($p) => $p->where('nama_partner', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        $depositInvoices = $query->paginate(20)->withQueryString();
        $partners = Partner::where('is_active', 1)->orderBy('nama_partner')->get(['id', 'nama_partner']);

        return view('deposit-invoices.index', compact('depositInvoices', 'partners'));
    }

    public function create(Request $request)
    {
        $partners          = Partner::where('is_active', 1)->orderBy('nama_partner')->get();
        $defaultDue        = (int) Setting::get('default_due_days', 14);
        $selectedPartnerId = $request->query('partner_id');

        return view('deposit-invoices.create', compact('partners', 'defaultDue', 'selectedPartnerId'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateDepositInvoice($request);

        DB::transaction(function () use ($validated) {
            $invoiceNo = $this->generateInvoiceNo();
            $partner   = Partner::findOrFail($validated['partner_id']);
            $dueDays   = $partner->payment_due_days ?? (int) Setting::get('default_due_days', 14);
            $dueDate   = $validated['due_date'] ?? date('Y-m-d', strtotime($validated['invoice_date'] . " +{$dueDays} days"));

            DepositInvoice::create([
                'invoice_no'   => $invoiceNo,
                'partner_id'   => $validated['partner_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date'     => $dueDate,
                'amount'       => $validated['amount'],
                'terbilang'    => ucfirst(Terbilang::convert($validated['amount'])),
                'status'       => 'DRAFT',
                'notes'        => $validated['notes'] ?? null,
                'is_finalized' => false,
                'created_by'   => auth()->id(),
                'updated_by'   => auth()->id(),
            ]);
        });

        return redirect()->route('deposit-invoices.index')->with('success', 'Invoice deposit berhasil dibuat.');
    }

    public function show(DepositInvoice $depositInvoice)
    {
        $depositInvoice->load(['partner', 'creator', 'depositRecord']);
        return view('deposit-invoices.show', compact('depositInvoice'));
    }

    public function edit(DepositInvoice $depositInvoice)
    {
        if ($depositInvoice->is_finalized) {
            return redirect()->route('deposit-invoices.show', $depositInvoice)
                ->with('error', 'Invoice deposit sudah final — tidak bisa diedit.');
        }

        $partners = Partner::where('is_active', 1)->orderBy('nama_partner')->get();
        return view('deposit-invoices.edit', compact('depositInvoice', 'partners'));
    }

    public function update(Request $request, DepositInvoice $depositInvoice)
    {
        if ($depositInvoice->is_finalized) {
            return redirect()->route('deposit-invoices.show', $depositInvoice)
                ->with('error', 'Invoice deposit sudah final — tidak bisa diedit.');
        }

        $validated = $this->validateDepositInvoice($request);

        $depositInvoice->update([
            'partner_id'   => $validated['partner_id'],
            'invoice_date' => $validated['invoice_date'],
            'due_date'     => $validated['due_date'] ?? null,
            'amount'       => $validated['amount'],
            'terbilang'    => ucfirst(Terbilang::convert($validated['amount'])),
            'notes'        => $validated['notes'] ?? null,
            'updated_by'   => auth()->id(),
        ]);

        return redirect()->route('deposit-invoices.show', $depositInvoice)
            ->with('success', 'Invoice deposit berhasil diperbarui.');
    }

    public function destroy(DepositInvoice $depositInvoice)
    {
        if ($depositInvoice->is_finalized) {
            return redirect()->route('deposit-invoices.index')
                ->with('error', 'Invoice deposit final tidak bisa dihapus.');
        }

        if ($depositInvoice->pdf_path) {
            Storage::disk('public')->delete($depositInvoice->pdf_path);
        }

        $depositInvoice->delete();

        return redirect()->route('deposit-invoices.index')
            ->with('success', 'Invoice deposit berhasil dihapus.');
    }

    public function finalize(DepositInvoice $depositInvoice)
    {
        if ($depositInvoice->is_finalized) {
            return redirect()->route('deposit-invoices.show', $depositInvoice)
                ->with('error', 'Invoice deposit sudah final.');
        }

        $depositInvoice->load(['partner', 'creator']);

        $settings = Setting::whereIn('key', [
            'company_name', 'company_address', 'company_phone', 'company_email',
            'company_npwp', 'bank_name', 'bank_account_no', 'bank_account_name',
            'invoice_notes', 'terms_conditions', 'logo_path',
        ])->pluck('value', 'key');

        $pdf = Pdf::loadView('deposit-invoices.pdf', compact('depositInvoice', 'settings'))
            ->setPaper('a4', 'portrait');

        $path = "deposit-invoices/{$depositInvoice->invoice_no}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        $depositInvoice->update([
            'is_finalized' => true,
            'status'       => 'SENT',
            'pdf_path'     => $path,
            'updated_by'   => auth()->id(),
        ]);

        return redirect()->route('deposit-invoices.show', $depositInvoice)
            ->with('success', 'Invoice deposit berhasil difinalisasi dan PDF tersimpan.');
    }

    public function pdf(DepositInvoice $depositInvoice)
    {
        // Always re-render from template so logo/settings changes are reflected
        $depositInvoice->load(['partner', 'creator']);
        $settings = Setting::whereIn('key', [
            'company_name', 'company_address', 'company_phone', 'company_email',
            'company_npwp', 'bank_name', 'bank_account_no', 'bank_account_name',
            'invoice_notes', 'terms_conditions', 'logo_path',
        ])->pluck('value', 'key');

        $pdf = Pdf::loadView('deposit-invoices.pdf', compact('depositInvoice', 'settings'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream($depositInvoice->invoice_no . '.pdf');
    }

    /**
     * Mark a finalized deposit invoice as PAID and record the TOPUP in partner_deposits.
     */
    public function markPaid(Request $request, DepositInvoice $depositInvoice)
    {
        if (!$depositInvoice->is_finalized) {
            return redirect()->route('deposit-invoices.show', $depositInvoice)
                ->with('error', 'Finalisasi invoice deposit terlebih dahulu.');
        }

        if ($depositInvoice->status === 'PAID') {
            return redirect()->route('deposit-invoices.show', $depositInvoice)
                ->with('error', 'Invoice deposit sudah berstatus PAID.');
        }

        $validated = $request->validate([
            'reference_no' => 'nullable|string|max:100',
            'notes'        => 'nullable|string',
            'paid_date'    => 'required|date',
        ]);

        DB::transaction(function () use ($depositInvoice, $validated) {
            // Create TOPUP record in partner_deposits
            $deposit = PartnerDeposit::create([
                'partner_id'   => $depositInvoice->partner_id,
                'type'         => 'TOPUP',
                'amount'       => $depositInvoice->amount,
                'reference_no' => $validated['reference_no'] ?? null,
                'notes'        => $validated['notes'] ?? "Top-up via Invoice Deposit {$depositInvoice->invoice_no}",
                'created_by'   => auth()->id(),
                'created_at'   => $validated['paid_date'] . ' ' . now()->format('H:i:s'),
            ]);

            $depositInvoice->update([
                'status'     => 'PAID',
                'deposit_id' => $deposit->id,
                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()->route('deposit-invoices.show', $depositInvoice)
            ->with('success', 'Deposit berhasil diterima dan saldo partner diperbarui.');
    }

    public function cancel(DepositInvoice $depositInvoice)
    {
        if ($depositInvoice->status === 'PAID') {
            return redirect()->route('deposit-invoices.show', $depositInvoice)
                ->with('error', 'Invoice deposit yang sudah PAID tidak bisa dibatalkan.');
        }

        $depositInvoice->update([
            'status'     => 'CANCELLED',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('deposit-invoices.show', $depositInvoice)
            ->with('success', 'Invoice deposit berhasil dibatalkan.');
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function generateInvoiceNo(): string
    {
        $prefix = Setting::get('deposit_invoice_prefix', 'DEP');
        $year   = now()->year;

        $last = DepositInvoice::where('invoice_no', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('invoice_no');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return "{$prefix}-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function validateDepositInvoice(Request $request): array
    {
        return $request->validate([
            'partner_id'   => 'required|exists:partners,id',
            'invoice_date' => 'required|date',
            'due_date'     => 'nullable|date|after_or_equal:invoice_date',
            'amount'       => 'required|numeric|min:1',
            'notes'        => 'nullable|string',
        ]);
    }
}
