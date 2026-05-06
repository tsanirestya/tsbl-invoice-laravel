<?php

namespace App\Http\Controllers;

use App\Helpers\Terbilang;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceLog;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['partner', 'creator'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhereHas('partner', fn($p) => $p->where('nama_partner', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
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

        $invoices = $query->paginate(20)->withQueryString();
        $partners = Partner::where('is_active', 1)->orderBy('nama_partner')->get(['id', 'nama_partner']);

        return view('invoices.index', compact('invoices', 'partners'));
    }

    public function create()
    {
        $partners   = Partner::where('is_active', 1)->orderBy('nama_partner')->get();
        $products   = Product::where('is_active', 1)->orderBy('product_name')->get();
        $defaultDue = (int) Setting::get('default_due_days', 14);

        return view('invoices.create', compact('partners', 'products', 'defaultDue'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateInvoice($request);
        $items     = $this->validateItems($request);

        DB::transaction(function () use ($validated, $items, $request) {
            $invoiceNo = $this->generateInvoiceNo();
            $partner   = Partner::findOrFail($validated['partner_id']);
            $dueDays   = $partner->payment_due_days ?? (int) Setting::get('default_due_days', 14);
            $dueDate   = $validated['due_date'] ?? date('Y-m-d', strtotime($validated['invoice_date'] . " +{$dueDays} days"));

            [$subtotal, $grandTotal, $itemsData] = $this->calcTotals($items, $validated['deposit'] ?? 0);

            $invoice = Invoice::create([
                'invoice_no'        => $invoiceNo,
                'partner_id'        => $validated['partner_id'],
                'guest_name'        => $validated['guest_name'] ?? null,
                'visit_date'        => $validated['visit_date'] ?? null,
                'booking_pass_no'   => $validated['booking_pass_no'] ?? null,
                'invoice_date'      => $validated['invoice_date'],
                'due_date'          => $dueDate,
                'dsi_transaction_no'=> $validated['dsi_transaction_no'] ?? null,
                'subtotal'          => $subtotal,
                'deposit'           => $validated['deposit'] ?? 0,
                'grand_total'       => $grandTotal,
                'terbilang'         => ucfirst(Terbilang::convert($grandTotal)),
                'payment_status'    => 'UNPAID',
                'notes'             => $validated['notes'] ?? null,
                'is_finalized'      => false,
                'created_by'        => auth()->id(),
                'updated_by'        => auth()->id(),
            ]);

            foreach ($itemsData as $idx => $item) {
                InvoiceItem::create(array_merge($item, [
                    'invoice_id' => $invoice->id,
                    'sort_order' => $idx,
                ]));
            }

            InvoiceLog::create([
                'invoice_id'  => $invoice->id,
                'action'      => 'CREATED',
                'description' => "Invoice {$invoice->invoice_no} dibuat",
                'created_by'  => auth()->id(),
                'created_at'  => now(),
            ]);
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dibuat.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['partner', 'items.product', 'payments', 'logs.creator', 'creator']);
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->is_finalized) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Invoice sudah final — tidak bisa diedit.');
        }

        $partners = Partner::where('is_active', 1)->orderBy('nama_partner')->get();
        $products = Product::where('is_active', 1)->orderBy('product_name')->get();
        $invoice->load('items.product');

        return view('invoices.edit', compact('invoice', 'partners', 'products'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->is_finalized) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Invoice sudah final — tidak bisa diedit.');
        }

        $validated = $this->validateInvoice($request);
        $items     = $this->validateItems($request);

        DB::transaction(function () use ($invoice, $validated, $items) {
            $partner = Partner::findOrFail($validated['partner_id']);
            $dueDays = $partner->payment_due_days ?? (int) Setting::get('default_due_days', 14);
            $dueDate = $validated['due_date'] ?? date('Y-m-d', strtotime($validated['invoice_date'] . " +{$dueDays} days"));

            [$subtotal, $grandTotal, $itemsData] = $this->calcTotals($items, $validated['deposit'] ?? 0);

            $invoice->update([
                'partner_id'        => $validated['partner_id'],
                'guest_name'        => $validated['guest_name'] ?? null,
                'visit_date'        => $validated['visit_date'] ?? null,
                'booking_pass_no'   => $validated['booking_pass_no'] ?? null,
                'invoice_date'      => $validated['invoice_date'],
                'due_date'          => $dueDate,
                'dsi_transaction_no'=> $validated['dsi_transaction_no'] ?? null,
                'subtotal'          => $subtotal,
                'deposit'           => $validated['deposit'] ?? 0,
                'grand_total'       => $grandTotal,
                'terbilang'         => ucfirst(Terbilang::convert($grandTotal)),
                'notes'             => $validated['notes'] ?? null,
                'updated_by'        => auth()->id(),
            ]);

            $invoice->items()->delete();
            foreach ($itemsData as $idx => $item) {
                InvoiceItem::create(array_merge($item, [
                    'invoice_id' => $invoice->id,
                    'sort_order' => $idx,
                ]));
            }

            InvoiceLog::create([
                'invoice_id'  => $invoice->id,
                'action'      => 'UPDATED',
                'description' => "Invoice {$invoice->invoice_no} diperbarui",
                'created_by'  => auth()->id(),
                'created_at'  => now(),
            ]);
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice berhasil diperbarui.');
    }

    public function duplicate(Invoice $invoice)
    {
        $newInvoice = DB::transaction(function () use ($invoice) {
            $invoice->load('items');

            $newInvoice = $invoice->replicate(['invoice_no', 'pdf_path', 'is_finalized', 'payment_status', 'created_by', 'updated_by']);
            $newInvoice->invoice_no   = $this->generateInvoiceNo();
            $newInvoice->invoice_date = now()->toDateString();
            $newInvoice->due_date     = now()->addDays(
                $invoice->partner->payment_due_days ?? (int) Setting::get('default_due_days', 14)
            )->toDateString();
            $newInvoice->payment_status = 'UNPAID';
            $newInvoice->is_finalized   = false;
            $newInvoice->pdf_path       = null;
            $newInvoice->created_by     = auth()->id();
            $newInvoice->updated_by     = auth()->id();
            $newInvoice->save();

            foreach ($invoice->items as $item) {
                $new = $item->replicate();
                $new->invoice_id = $newInvoice->id;
                $new->save();
            }

            InvoiceLog::create([
                'invoice_id'  => $newInvoice->id,
                'action'      => 'CREATED',
                'description' => "Duplikat dari invoice {$invoice->invoice_no}",
                'created_by'  => auth()->id(),
                'created_at'  => now(),
            ]);

            return $newInvoice;
        });

        return redirect()->route('invoices.edit', $newInvoice)->with('success', "Invoice {$newInvoice->invoice_no} dibuat sebagai duplikat.");
    }

    public function finalize(Invoice $invoice)
    {
        if ($invoice->is_finalized) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Invoice sudah final.');
        }

        if ($invoice->items()->count() === 0) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Invoice harus memiliki minimal 1 item.');
        }

        $invoice->load(['partner', 'items', 'creator']);

        $settings = Setting::whereIn('key', [
            'company_name', 'company_address', 'company_phone', 'company_email',
            'company_npwp', 'bank_name', 'bank_account_no', 'bank_account_name',
            'invoice_notes', 'terms_conditions', 'logo_path',
        ])->pluck('value', 'key');

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'settings'))
            ->setPaper('a4', 'portrait');

        $path = "invoices/{$invoice->invoice_no}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        $invoice->update([
            'is_finalized' => true,
            'pdf_path'     => $path,
            'updated_by'   => auth()->id(),
        ]);

        InvoiceLog::create([
            'invoice_id'  => $invoice->id,
            'action'      => 'FINALIZED',
            'description' => "Invoice {$invoice->invoice_no} difinalisasi dan PDF dibuat",
            'created_by'  => auth()->id(),
            'created_at'  => now(),
        ]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice berhasil difinalisasi. PDF tersimpan.');
    }

    public function pdf(Invoice $invoice)
    {
        if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
            return response()->file(Storage::disk('public')->path($invoice->pdf_path), [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $invoice->invoice_no . '.pdf"',
            ]);
        }

        // Draft preview — not stored
        $invoice->load(['partner', 'items', 'creator']);
        $settings = Setting::whereIn('key', [
            'company_name', 'company_address', 'company_phone', 'company_email',
            'company_npwp', 'bank_name', 'bank_account_no', 'bank_account_name',
            'invoice_notes', 'terms_conditions', 'logo_path',
        ])->pluck('value', 'key');

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'settings'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream($invoice->invoice_no . '.pdf');
    }

    public function markOverdue()
    {
        $invoices = Invoice::where('is_finalized', true)
            ->where('payment_status', 'UNPAID')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            $invoice->update(['payment_status' => 'OVERDUE']);
            InvoiceLog::create([
                'invoice_id'  => $invoice->id,
                'action'      => 'OVERDUE',
                'description' => "Invoice jatuh tempo {$invoice->due_date->format('d/m/Y')} — diubah ke OVERDUE",
                'old_value'   => 'UNPAID',
                'new_value'   => 'OVERDUE',
                'created_at'  => now(),
            ]);
            $count++;
        }

        return redirect()->back()
            ->with('success', "{$count} invoice diupdate ke OVERDUE.");
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->is_finalized) {
            return redirect()->route('invoices.index')->with('error', 'Invoice final tidak bisa dihapus.');
        }

        if ($invoice->pdf_path) {
            Storage::disk('public')->delete($invoice->pdf_path);
        }

        $invoice->items()->delete();
        $invoice->logs()->delete();
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dihapus.');
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function generateInvoiceNo(): string
    {
        $prefix = Setting::get('invoice_prefix', 'INV');
        $year   = now()->year;

        $last = Invoice::where('invoice_no', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->value('invoice_no');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return "{$prefix}-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function calcTotals(array $items, float $deposit): array
    {
        $subtotal   = 0;
        $itemsData  = [];

        foreach ($items as $item) {
            $amount     = $item['pax'] * $item['price_per_pax'];
            $subtotal  += $amount;
            $itemsData[] = [
                'product_id'    => $item['product_id'] ?? null,
                'product_name'  => $item['product_name'],
                'pax'           => $item['pax'],
                'price_per_pax' => $item['price_per_pax'],
                'amount'        => $amount,
            ];
        }

        return [$subtotal, max(0, $subtotal - $deposit), $itemsData];
    }

    private function validateInvoice(Request $request): array
    {
        return $request->validate([
            'partner_id'         => 'required|exists:partners,id',
            'guest_name'         => 'nullable|string|max:200',
            'visit_date'         => 'nullable|date',
            'booking_pass_no'    => 'nullable|string|max:100',
            'invoice_date'       => 'required|date',
            'due_date'           => 'nullable|date|after_or_equal:invoice_date',
            'dsi_transaction_no' => 'nullable|string|max:100',
            'deposit'            => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string',
        ]);
    }

    private function validateItems(Request $request): array
    {
        $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.product_name'   => 'required|string|max:200',
            'items.*.pax'            => 'required|integer|min:1',
            'items.*.price_per_pax'  => 'required|numeric|min:0',
        ]);

        return $request->input('items');
    }
}
