<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Partner;
use App\Models\PaymentMemo;
use App\Models\PaymentMemoInvoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PaymentMemoController extends Controller
{
    public function index()
    {
        $memos = PaymentMemo::with(['partner', 'creator', 'memoInvoices'])
            ->latest()
            ->paginate(20);

        return view('payment-memos.index', compact('memos'));
    }

    public function create(Request $request)
    {
        $partners = Partner::where('is_active', true)
            ->orderBy('nama_partner')
            ->get(['id', 'nama_partner']);

        $selectedPartnerId = $request->query('partner_id');
        $selectedPartner   = null;
        $outstandingInvoices = collect();

        if ($selectedPartnerId) {
            $selectedPartner = Partner::find($selectedPartnerId);
            if ($selectedPartner) {
                $outstandingInvoices = $this->getOutstandingInvoices($selectedPartnerId);
            }
        }

        return view('payment-memos.create', compact('partners', 'selectedPartner', 'outstandingInvoices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'partner_id'  => 'required|exists:partners,id',
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:invoices,id',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $partner = Partner::findOrFail($validated['partner_id']);

        // Verify all invoices belong to partner and are outstanding
        $invoices = Invoice::whereIn('id', $validated['invoice_ids'])
            ->where('partner_id', $partner->id)
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])
            ->get();

        if ($invoices->isEmpty()) {
            return back()->withInput()->with('error', 'Tidak ada invoice outstanding yang valid.');
        }

        $memo = PaymentMemo::create([
            'memo_no'          => PaymentMemo::generateMemoNo(),
            'partner_id'       => $partner->id,
            'memo_date'        => Carbon::today(),
            'payment_deadline' => Carbon::today()->addDays(7),
            'notes'            => $validated['notes'] ?? null,
            'created_by'       => auth()->id(),
        ]);

        foreach ($invoices as $invoice) {
            $totalPaid    = (float) $invoice->payments()->sum('amount');
            $sisaTagihan  = max(0, (float) $invoice->grand_total - $totalPaid);

            PaymentMemoInvoice::create([
                'payment_memo_id' => $memo->id,
                'invoice_id'      => $invoice->id,
                'grand_total'     => $invoice->grand_total,
                'sisa_tagihan'    => $sisaTagihan,
            ]);
        }

        return redirect()->route('payment-memos.show', $memo)
            ->with('success', "Memo {$memo->memo_no} berhasil dibuat.");
    }

    public function show(PaymentMemo $paymentMemo)
    {
        $paymentMemo->load(['partner', 'creator', 'memoInvoices.invoice']);

        return view('payment-memos.show', compact('paymentMemo'));
    }

    public function pdf(PaymentMemo $paymentMemo)
    {
        $paymentMemo->load(['partner', 'creator', 'memoInvoices.invoice']);

        $settings = [
            'company_name'      => Setting::get('company_name', 'PT. TSBL'),
            'company_address'   => Setting::get('company_address', ''),
            'company_phone'     => Setting::get('company_phone', ''),
            'company_email'     => Setting::get('company_email', ''),
            'company_logo'      => Setting::get('company_logo_path'),
            'bank_name'         => Setting::get('bank_name', ''),
            'bank_account_no'   => Setting::get('bank_account_no', ''),
            'bank_account_name' => Setting::get('bank_account_name', ''),
        ];

        $pdf = Pdf::loadView('payment-memos.pdf', compact('paymentMemo', 'settings'))
            ->setPaper('a4', 'portrait');

        $filename = 'memo-' . $paymentMemo->memo_no . '.pdf';

        return $pdf->stream($filename);
    }

    public function destroy(PaymentMemo $paymentMemo)
    {
        $memoNo = $paymentMemo->memo_no;

        // cascadeOnDelete handles payment_memo_invoices rows
        $paymentMemo->delete();

        return redirect()->route('payment-memos.index')
            ->with('success', "Memo {$memoNo} berhasil dihapus.");
    }

    /** AJAX: outstanding invoices for a partner */
    public function outstandingInvoices(Partner $partner)
    {
        $invoices = $this->getOutstandingInvoices($partner->id);

        return response()->json($invoices->map(function ($inv) {
            $totalPaid   = (float) $inv->payments()->sum('amount');
            $sisa        = max(0, (float) $inv->grand_total - $totalPaid);
            $daysToJt    = now()->startOfDay()->diffInDays($inv->due_date, false);

            return [
                'id'           => $inv->id,
                'invoice_no'   => $inv->invoice_no,
                'invoice_date' => $inv->invoice_date?->format('d/m/Y'),
                'due_date'     => $inv->due_date?->format('d/m/Y'),
                'grand_total'  => (float) $inv->grand_total,
                'total_paid'   => $totalPaid,
                'sisa'         => $sisa,
                'status'       => $inv->payment_status,
                'days_to_jt'   => $daysToJt,
            ];
        })->values());
    }

    private function getOutstandingInvoices(int $partnerId)
    {
        return Invoice::where('partner_id', $partnerId)
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])
            ->orderByRaw("CASE payment_status WHEN 'OVERDUE' THEN 0 WHEN 'PARTIAL' THEN 1 ELSE 2 END")
            ->orderBy('due_date')
            ->get();
    }
}
