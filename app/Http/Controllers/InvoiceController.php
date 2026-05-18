<?php

namespace App\Http\Controllers;

use App\Helpers\Terbilang;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceLog;
use App\Models\Partner;
use App\Models\PartnerDeposit;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\TransactionImportRow;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

        // Summary stats (always from full dataset, not filtered)
        $stats = [
            'total'          => Invoice::count(),
            'unpaid'         => Invoice::whereIn('payment_status', ['UNPAID'])->count(),
            'unpaid_amount'  => Invoice::whereIn('payment_status', ['UNPAID'])->sum('grand_total'),
            'overdue'        => Invoice::where('payment_status', 'OVERDUE')->count(),
            'overdue_amount' => Invoice::where('payment_status', 'OVERDUE')->sum('grand_total'),
            'paid'           => Invoice::where('payment_status', 'PAID')->count(),
            'paid_amount'    => Invoice::where('payment_status', 'PAID')->sum('grand_total'),
        ];

        return view('invoices.index', compact('invoices', 'partners', 'stats'));
    }

    public function create(Request $request)
    {
        $partners   = Partner::where('is_active', 1)->orderBy('nama_partner')->get();
        $products   = Product::where('is_active', 1)->orderBy('product_name')->get();
        $defaultDue = (int) Setting::get('default_due_days', 14);

        // Mode: dari antrian (transaction_no) — load semua baris 1 transaksi sekaligus
        $importRows = collect();
        $importRow  = null; // backward compat

        if ($request->filled('transaction_no')) {
            $importRows = TransactionImportRow::with(['product', 'import', 'anomalies'])
                ->where('transaction_no', $request->transaction_no)
                ->whereIn('status', ['valid', 'anomaly'])
                ->whereDoesntHave('invoice') // Exclude rows already linked to an invoice
                ->get();
        } elseif ($request->filled('import_row_id')) {
            // Mode lama: single row
            $importRow  = TransactionImportRow::with(['product', 'import', 'anomalies'])->find($request->import_row_id);
            // Only use if not already linked to another invoice
            if ($importRow && !$importRow->invoice()->exists()) {
                $importRows = collect([$importRow]);
            } elseif ($importRow && $importRow->invoice()->exists()) {
                $importRow = null; // Already has invoice, treat as manual
            }
        }

        // Booking Pass No. validation — remark dari DSI vs reservations table
        $bookingPassNo          = null;
        $bookingPassStatus      = null;
        $bookingPassReservation = null;
        if ($importRows->isNotEmpty()) {
            $remark = trim($importRows->first()->remark ?? '');
            if ($remark !== '') {
                $bookingPassReservation = Reservation::with('partner:id,nama_partner')
                    ->where('reservation_no', $remark)
                    ->first(['id', 'reservation_no', 'guest_name', 'partner_id']);
                $bookingPassNo     = $remark;
                $bookingPassStatus = $bookingPassReservation ? 'found' : 'not_found';
            } else {
                $bookingPassStatus = 'empty';
            }
        }

        return view('invoices.create', compact(
            'partners', 'products', 'defaultDue',
            'importRow', 'importRows',
            'bookingPassNo', 'bookingPassStatus', 'bookingPassReservation'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateInvoice($request);
        $items     = $this->validateItems($request);

        $depositAmount = (float) ($validated['deposit'] ?? 0);

        if ($depositAmount > 0) {
            $partner = Partner::findOrFail($validated['partner_id']);
            $balance = $partner->depositBalance();
            if ($depositAmount > $balance) {
                return back()->withInput()->withErrors(['deposit' => "Deposit melebihi saldo tersedia (Rp " . number_format($balance, 0, ',', '.') . ")."]);
            }
        }

        DB::transaction(function () use ($validated, $items, $depositAmount) {
            $invoiceNo = $this->generateInvoiceNo();
            $partner   = Partner::lockForUpdate()->findOrFail($validated['partner_id']);

            // Credit validation inside transaction after partner lock — prevents TOCTOU race condition (F-012)
            [$subtotalForCredit] = $this->calcTotals($items, 0);
            $creditLimit = (float) $partner->limit_credit;

            if ($creditLimit > 0) {
                $threshold   = (float) Setting::get('credit_warning_threshold', 80);
                $creditAfter = $partner->creditUsed() + $subtotalForCredit;
                $utilAfter   = ($creditAfter / $creditLimit) * 100;

                if ($creditAfter > $creditLimit) {
                    $overrideReason = trim($validated['credit_override_reason'] ?? '');
                    if (empty($overrideReason)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'credit_override_reason' => 'Alasan override wajib diisi karena invoice ini akan melebihi credit limit partner.',
                        ]);
                    }
                    session()->flash('warning', 'Invoice melampaui credit limit. Tersimpan dengan override reason.');
                } elseif ($utilAfter >= $threshold) {
                    session()->flash('warning', 'Utilisasi kredit partner mencapai ' . number_format($utilAfter, 1) . '% setelah invoice ini.');
                }
            }
            $dueDays   = $partner->payment_due_days ?? (int) Setting::get('default_due_days', 14);
            $dueDate   = $validated['due_date'] ?? date('Y-m-d', strtotime($validated['invoice_date'] . " +{$dueDays} days"));

            [$subtotal, , $itemsData] = $this->calcTotals($items, 0);

            // Clamp deposit to subtotal
            $depositAmount = min($depositAmount, $subtotal);

            // Grand total = subtotal; deposit is a payment method, not a deduction
            $grandTotal = $subtotal;

            $invoice = Invoice::create([
                'invoice_no'              => $invoiceNo,
                'partner_id'              => $validated['partner_id'],
                'guest_name'              => $validated['guest_name'],
                'visit_date'              => $validated['visit_date'],
                'booking_pass_no'         => $validated['booking_pass_no'],
                'invoice_date'            => $validated['invoice_date'],
                'due_date'                => $dueDate,
                'dsi_transaction_no'      => $validated['dsi_transaction_no'],
                'subtotal'                => $subtotal,
                'deposit'                 => $depositAmount,
                'grand_total'             => $grandTotal,
                'terbilang'               => ucfirst(Terbilang::convert($grandTotal)),
                'payment_status'          => 'UNPAID',
                'payment_method'          => $validated['payment_method'] ?? null,
                'notes'                   => $validated['notes'] ?? null,
                'credit_override_reason'  => $validated['credit_override_reason'] ?? null,
                'import_row_id'           => $validated['import_row_id'] ?? null,
                'is_finalized'            => false,
                'created_by'              => auth()->id(),
                'updated_by'              => auth()->id(),
            ]);

            foreach ($itemsData as $idx => $item) {
                InvoiceItem::create(array_merge($item, [
                    'invoice_id' => $invoice->id,
                    'sort_order' => $idx,
                ]));
            }

            if ($depositAmount > 0) {
                // Deduct from partner deposit ledger
                PartnerDeposit::create([
                    'partner_id' => $validated['partner_id'],
                    'type'       => 'DEDUCTION',
                    'amount'     => $depositAmount,
                    'invoice_id' => $invoice->id,
                    'notes'      => "Dipakai di invoice {$invoice->invoice_no}",
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                ]);

                // Record as payment so invoice status is tracked correctly
                Payment::create([
                    'invoice_id'     => $invoice->id,
                    'amount'         => $depositAmount,
                    'payment_date'   => $validated['invoice_date'],
                    'payment_method' => 'Deposit',
                    'reference_no'   => null,
                    'proof_file'     => null,
                    'notes'          => "Dibayar dengan deposit partner",
                    'created_by'     => auth()->id(),
                    'created_at'     => now(),
                ]);

                $invoice->recalcStatus();
            }

            InvoiceLog::create([
                'invoice_id'  => $invoice->id,
                'action'      => 'CREATED',
                'description' => "Invoice {$invoice->invoice_no} dibuat" . ($depositAmount > 0 ? " (deposit Rp " . number_format($depositAmount, 0, ',', '.') . ")" : ""),
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

        $validated     = $this->validateInvoice($request, $invoice->id);
        $items         = $this->validateItems($request);
        $newDeposit    = (float) ($validated['deposit'] ?? 0);
        $oldDeposit    = (float) $invoice->deposit;

        if ($newDeposit > 0) {
            $partner       = Partner::findOrFail($validated['partner_id']);
            $currentBalance = $partner->depositBalance();
            // Available balance = current balance + old deduction (to be reversed)
            $availableBalance = $currentBalance + $oldDeposit;
            if ($newDeposit > $availableBalance) {
                return back()->withInput()->withErrors(['deposit' => "Deposit melebihi saldo tersedia (Rp " . number_format($availableBalance, 0, ',', '.') . ")."]);
            }
        }

        DB::transaction(function () use ($invoice, $validated, $items, $newDeposit, $oldDeposit) {
            $partner = Partner::lockForUpdate()->findOrFail($validated['partner_id']);

            // Credit validation inside transaction after partner lock — prevents TOCTOU race condition (F-012)
            [$subtotalForCredit] = $this->calcTotals($items, 0);
            $creditLimit = (float) $partner->limit_credit;

            if ($creditLimit > 0) {
                $threshold       = (float) Setting::get('credit_warning_threshold', 80);
                // Subtract current invoice grand_total only if it's still outstanding (included in creditUsed)
                $invoiceInCredit = in_array($invoice->payment_status, ['UNPAID', 'PARTIAL', 'OVERDUE']);
                $baseUsed        = $partner->creditUsed() - ($invoiceInCredit ? (float) $invoice->grand_total : 0);
                $creditAfter     = $baseUsed + $subtotalForCredit;
                $utilAfter       = ($creditAfter / $creditLimit) * 100;

                if ($creditAfter > $creditLimit) {
                    $overrideReason = trim($validated['credit_override_reason'] ?? '');
                    if (empty($overrideReason)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'credit_override_reason' => 'Alasan override wajib diisi karena invoice ini akan melebihi credit limit partner.',
                        ]);
                    }
                    session()->flash('warning', 'Invoice melampaui credit limit. Tersimpan dengan override reason.');
                } elseif ($utilAfter >= $threshold) {
                    session()->flash('warning', 'Utilisasi kredit partner mencapai ' . number_format($utilAfter, 1) . '% setelah invoice ini.');
                }
            }
            $dueDays = $partner->payment_due_days ?? (int) Setting::get('default_due_days', 14);
            $dueDate = $validated['due_date'] ?? date('Y-m-d', strtotime($validated['invoice_date'] . " +{$dueDays} days"));

            [$subtotal, , $itemsData] = $this->calcTotals($items, 0);
            $depositAmount = min($newDeposit, $subtotal);

            // Grand total = subtotal; deposit is a payment method, not a deduction
            $grandTotal = $subtotal;

            $invoice->update([
                'partner_id'             => $validated['partner_id'],
                'guest_name'             => $validated['guest_name'],
                'visit_date'             => $validated['visit_date'],
                'booking_pass_no'        => $validated['booking_pass_no'],
                'invoice_date'           => $validated['invoice_date'],
                'due_date'               => $dueDate,
                'dsi_transaction_no'     => $validated['dsi_transaction_no'],
                'subtotal'               => $subtotal,
                'deposit'                => $depositAmount,
                'grand_total'            => $grandTotal,
                'terbilang'              => ucfirst(Terbilang::convert($grandTotal)),
                'payment_method'         => $validated['payment_method'] ?? null,
                'notes'                  => $validated['notes'] ?? null,
                'credit_override_reason' => $validated['credit_override_reason'] ?? null,
                'updated_by'             => auth()->id(),
            ]);

            $invoice->items()->delete();
            foreach ($itemsData as $idx => $item) {
                InvoiceItem::create(array_merge($item, [
                    'invoice_id' => $invoice->id,
                    'sort_order' => $idx,
                ]));
            }

            // Reverse old DEDUCTION and old deposit payment, create new ones if needed
            PartnerDeposit::where('invoice_id', $invoice->id)->where('type', 'DEDUCTION')->delete();
            Payment::where('invoice_id', $invoice->id)->where('payment_method', 'Deposit')->delete();

            if ($depositAmount > 0) {
                PartnerDeposit::create([
                    'partner_id' => $validated['partner_id'],
                    'type'       => 'DEDUCTION',
                    'amount'     => $depositAmount,
                    'invoice_id' => $invoice->id,
                    'notes'      => "Dipakai di invoice {$invoice->invoice_no}",
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                ]);

                Payment::create([
                    'invoice_id'     => $invoice->id,
                    'amount'         => $depositAmount,
                    'payment_date'   => $validated['invoice_date'],
                    'payment_method' => 'Deposit',
                    'reference_no'   => null,
                    'proof_file'     => null,
                    'notes'          => "Dibayar dengan deposit partner",
                    'created_by'     => auth()->id(),
                    'created_at'     => now(),
                ]);
            }

            $invoice->recalcStatus();

            InvoiceLog::create([
                'invoice_id'  => $invoice->id,
                'action'      => 'UPDATED',
                'description' => "Invoice {$invoice->invoice_no} diperbarui" . ($depositAmount > 0 ? " (deposit Rp " . number_format($depositAmount, 0, ',', '.') . ")" : ""),
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

            $newInvoice = $invoice->replicate(['invoice_no', 'pdf_path', 'is_finalized', 'payment_status', 'created_by', 'updated_by', 'deposit']);
            $newInvoice->deposit = 0;
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
            'is_finalized'           => true,
            'pdf_path'               => $path,
            'finalized_by'           => auth()->id(),
            'finalized_by_signature' => auth()->user()->signature_image,
            'updated_by'             => auth()->id(),
        ]);

        InvoiceLog::create([
            'invoice_id'  => $invoice->id,
            'action'      => 'FINALIZED',
            'description' => "Invoice {$invoice->invoice_no} difinalisasi oleh " . auth()->user()->full_name,
            'created_by'  => auth()->id(),
            'created_at'  => now(),
        ]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice berhasil difinalisasi. PDF tersimpan.');
    }

    public function pdf(Invoice $invoice)
    {
        // Always re-render from template so logo/settings changes are reflected
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

    public function autoCreateProducts(Invoice $invoice)
    {
        if (!$invoice->is_finalized) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Invoice belum difinalisasi.');
        }

        if (!$invoice->dsi_transaction_no) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Invoice tidak memiliki No. Transaksi DSI.');
        }

        $importRows = TransactionImportRow::where('transaction_no', $invoice->dsi_transaction_no)->get();

        if ($importRows->isEmpty()) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Data transaksi DSI tidak ditemukan.');
        }

        $created = 0;
        $linked  = 0;

        DB::transaction(function () use ($invoice, $importRows, &$created, &$linked) {
            $invoice->load('items');

            foreach ($importRows as $row) {
                if (!$row->ticket_type) {
                    continue;
                }

                // Find or create product by dsi_code
                $product = Product::where('dsi_code', $row->ticket_type)->first();

                if (!$product) {
                    $komisiPerPax = ($row->komisi_amount && $row->qty > 0)
                        ? round($row->komisi_amount / $row->qty, 2)
                        : 0;

                    $product = Product::create([
                        'product_name'   => $row->ticket_name ?? $row->ticket_type,
                        'dsi_code'       => $row->ticket_type,
                        'default_price'  => $row->unit_price ?? 0,
                        'publish_rate'   => $row->publish_rate ?? $row->unit_price ?? 0,
                        'komisi'         => $komisiPerPax,
                        'nett_price'     => $row->nett_price ?? $row->unit_price ?? 0,
                        'unit_price_dsi' => $row->unit_price ?? 0,
                        'unit'           => 'Pax',
                        'is_active'      => true,
                        'created_by'     => auth()->id(),
                    ]);
                    $created++;

                    // Update import row to reflect the new match
                    $row->update([
                        'matched_product_id' => $product->id,
                        'match_method'       => 'exact',
                    ]);
                } elseif (!$row->matched_product_id) {
                    $row->update([
                        'matched_product_id' => $product->id,
                        'match_method'       => 'exact',
                    ]);
                }

                // Link invoice items that match this ticket name and have no product_id yet
                $updated = $invoice->items()
                    ->whereNull('product_id')
                    ->where('product_name', $row->ticket_name)
                    ->update(['product_id' => $product->id]);

                $linked += $updated;
            }

            // Second pass: match remaining unlinked items by product name
            foreach ($invoice->items()->whereNull('product_id')->get() as $item) {
                $product = Product::where('product_name', $item->product_name)->first();
                if ($product) {
                    $item->update(['product_id' => $product->id]);
                    $linked++;
                }
            }
        });

        $msg = [];
        if ($created > 0) {
            $msg[] = "{$created} produk baru dibuat";
        }
        if ($linked > 0) {
            $msg[] = "{$linked} item ditautkan ke produk";
        }
        if (empty($msg)) {
            return redirect()->route('invoices.show', $invoice)->with('info', 'Semua item sudah tertaut ke produk.');
        }

        return redirect()->route('invoices.show', $invoice)->with('success', implode(', ', $msg) . '.');
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

        // Reverse deposit DEDUCTION before deleting
        PartnerDeposit::where('invoice_id', $invoice->id)->where('type', 'DEDUCTION')->delete();

        $invoice->items()->delete();
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
            ->lockForUpdate()
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

    private function validateInvoice(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'partner_id'         => 'required|exists:partners,id',
            'guest_name'         => 'required|string|max:200',
            'visit_date'         => 'required|date',
            'booking_pass_no'    => 'required|string|max:100',
            'invoice_date'       => 'required|date',
            'due_date'           => 'nullable|date|after_or_equal:invoice_date',
            // 1 dsi_transaction_no = 1 invoice; null exempt (manual invoices)
            'dsi_transaction_no' => [
                'nullable', 'string', 'max:100',
                Rule::unique('invoices', 'dsi_transaction_no')->ignore($ignoreId),
            ],
            'deposit'                   => 'nullable|numeric|min:0',
            'payment_method'            => 'nullable|string|in:transfer_nett,transfer_gross,ots_nett,ots_gross',
            'notes'                     => 'nullable|string',
            'credit_override_reason'    => 'nullable|string|max:500',
            'import_row_id'             => 'nullable|integer|exists:transaction_import_rows,id',
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
