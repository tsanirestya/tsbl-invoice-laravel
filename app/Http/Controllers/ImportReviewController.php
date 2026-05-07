<?php

namespace App\Http\Controllers;

use App\Models\TransactionImport;
use App\Models\TransactionImportRow;
use Illuminate\Http\Request;

class ImportReviewController extends Controller
{
    public function approveRows(Request $request, TransactionImport $import)
    {
        $request->validate([
            'row_ids'   => ['required', 'array'],
            'row_ids.*' => ['integer'],
        ]);

        TransactionImportRow::whereIn('id', $request->row_ids)
            ->where('import_id', $import->id)
            ->update([
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        $this->recalcImportCounts($import);

        return back()->with('success', count($request->row_ids) . ' baris disetujui.');
    }

    public function rejectRows(Request $request, TransactionImport $import)
    {
        $request->validate([
            'row_ids'   => ['required', 'array'],
            'row_ids.*' => ['integer'],
        ]);

        TransactionImportRow::whereIn('id', $request->row_ids)
            ->where('import_id', $import->id)
            ->update(['status' => 'rejected']);

        $this->recalcImportCounts($import);

        return back()->with('success', count($request->row_ids) . ' baris ditolak.');
    }

    public function overrideRow(Request $request, TransactionImport $import)
    {
        $request->validate([
            'row_id'          => ['required', 'integer'],
            'override_reason' => ['required', 'string', 'max:500'],
        ]);

        $row = TransactionImportRow::where('id', $request->row_id)
            ->where('import_id', $import->id)
            ->firstOrFail();

        $row->update([
            'is_approved'     => true,
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
            'override_reason' => $request->override_reason,
        ]);

        return back()->with('success', 'Override disimpan.');
    }

    /**
     * Override ALL unapproved anomaly rows that share the same ticket_name.
     */
    public function overrideGroup(Request $request, TransactionImport $import)
    {
        $request->validate([
            'ticket_name'     => ['required', 'string'],
            'override_reason' => ['required', 'string', 'max:500'],
        ]);

        $count = TransactionImportRow::where('import_id', $import->id)
            ->where('ticket_name', $request->ticket_name)
            ->where('status', 'anomaly')
            ->where('is_approved', false)
            ->update([
                'is_approved'     => true,
                'approved_by'     => auth()->id(),
                'approved_at'     => now(),
                'override_reason' => $request->override_reason,
            ]);

        $this->recalcImportCounts($import);

        return back()->with('success', "{$count} baris dengan nama tiket yang sama telah di-override.");
    }

    /**
     * Reject ALL unapproved anomaly rows that share the same ticket_name.
     */
    public function rejectGroup(Request $request, TransactionImport $import)
    {
        $request->validate([
            'ticket_name' => ['required', 'string'],
        ]);

        $count = TransactionImportRow::where('import_id', $import->id)
            ->where('ticket_name', $request->ticket_name)
            ->where('status', 'anomaly')
            ->where('is_approved', false)
            ->update(['status' => 'rejected']);

        $this->recalcImportCounts($import);

        return back()->with('success', "{$count} baris ditolak.");
    }

    public function finalizeImport(Request $request, TransactionImport $import)
    {
        // Cannot finalize if any anomaly row is still unhandled
        $unhandled = $import->rows()
            ->where('status', 'anomaly')
            ->where('is_approved', false)
            ->count();

        if ($unhandled > 0) {
            return back()->withErrors(['finalize' => "{$unhandled} baris anomaly belum di-handle."]);
        }

        $import->update([
            'status'      => 'done',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('imports.show', $import)
            ->with('success', 'Import difinalisasi.');
    }

    /**
     * Adjust publish_rate / nett_price for all pending anomaly rows in a ticket_name group.
     */
    public function adjustGroupPricing(Request $request, TransactionImport $import)
    {
        $request->validate([
            'ticket_name'     => ['required', 'string'],
            'publish_rate'    => ['required', 'numeric', 'min:0'],
            'nett_price'      => ['required', 'numeric', 'min:0'],
            'override_reason' => ['required', 'string', 'max:500'],
        ]);

        $rows = TransactionImportRow::where('import_id', $import->id)
            ->where('ticket_name', $request->ticket_name)
            ->where('status', 'anomaly')
            ->where('is_approved', false)
            ->get();

        foreach ($rows as $row) {
            $unitPrice   = (float) $row->unit_price;
            $publishRate = (float) $request->publish_rate;
            $nettPrice   = (float) $request->nett_price;
            $komisiRate  = (float) $row->komisi_rate;
            $qty         = (int) $row->qty;

            if (abs($unitPrice - $publishRate) < 0.01) {
                $komisi = $komisiRate * $qty;
            } elseif (abs($unitPrice - $nettPrice) < 0.01) {
                $komisi = 0;
            } else {
                $komisi = null;
            }

            $row->update([
                'publish_rate'    => $request->publish_rate,
                'nett_price'      => $request->nett_price,
                'komisi_amount'   => $komisi,
                'is_approved'     => true,
                'approved_by'     => auth()->id(),
                'approved_at'     => now(),
                'override_reason' => $request->override_reason,
            ]);
        }

        $this->recalcImportCounts($import);

        return back()->with('success', $rows->count() . ' baris disesuaikan harganya.');
    }

    /**
     * Reassign a different product to all pending anomaly rows in a ticket_name group.
     */
    public function reassignProduct(Request $request, TransactionImport $import)
    {
        $request->validate([
            'ticket_name'     => ['required', 'string'],
            'product_id'      => ['required', 'integer', 'exists:products,id'],
            'override_reason' => ['required', 'string', 'max:500'],
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);

        $rows = TransactionImportRow::where('import_id', $import->id)
            ->where('ticket_name', $request->ticket_name)
            ->where('status', 'anomaly')
            ->where('is_approved', false)
            ->get();

        foreach ($rows as $row) {
            $unitPrice   = (float) $row->unit_price;
            $publishRate = (float) $product->publish_rate;
            $nettPrice   = (float) $product->nett_price;
            $komisiRate  = (float) $product->komisi;
            $qty         = (int) $row->qty;

            if (abs($unitPrice - $publishRate) < 0.01) {
                $komisi = $komisiRate * $qty;
            } elseif (abs($unitPrice - $nettPrice) < 0.01) {
                $komisi = 0;
            } else {
                $komisi = null;
            }

            $row->update([
                'matched_product_id' => $product->id,
                'match_method'       => 'manual',
                'publish_rate'       => $product->publish_rate,
                'nett_price'         => $product->nett_price,
                'komisi_rate'        => $product->komisi,
                'komisi_amount'      => $komisi,
                'is_approved'        => true,
                'approved_by'        => auth()->id(),
                'approved_at'        => now(),
                'override_reason'    => $request->override_reason,
            ]);
        }

        $this->recalcImportCounts($import);

        return back()->with('success', $rows->count() . ' baris dipindahkan ke produk: ' . $product->product_name);
    }

    /**
     * AJAX: return top similar products by dsi_code similarity to the given ticket_name.
     */
    public function similarProducts(Request $request, TransactionImport $import)
    {
        $ticketName = strtoupper(trim($request->input('ticket_name', '')));

        $products = \App\Models\Product::where('is_active', true)
            ->whereNotNull('dsi_code')
            ->where('dsi_code', '!=', '')
            ->get(['id', 'product_name', 'dsi_code', 'publish_rate', 'nett_price', 'komisi']);

        $scored = $products->map(function ($p) use ($ticketName) {
            similar_text($ticketName, strtoupper(trim($p->dsi_code)), $pct);
            return [
                'id'           => $p->id,
                'product_name' => $p->product_name,
                'dsi_code'     => $p->dsi_code,
                'publish_rate' => (float) $p->publish_rate,
                'nett_price'   => (float) $p->nett_price,
                'komisi'       => (float) $p->komisi,
                'score'        => round($pct, 1),
            ];
        })->sortByDesc('score')->take(8)->values();

        return response()->json($scored);
    }

    private function recalcImportCounts(TransactionImport $import): void
    {
        $rows = $import->rows();

        $import->update([
            'valid_rows'   => (clone $rows)->where('status', 'valid')->count(),
            'anomaly_rows' => (clone $rows)->where('status', 'anomaly')->count(),
        ]);
    }
}
