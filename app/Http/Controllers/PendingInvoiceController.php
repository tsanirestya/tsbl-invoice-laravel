<?php

namespace App\Http\Controllers;

use App\Models\TransactionImportRow;
use Illuminate\Http\Request;

class PendingInvoiceController extends Controller
{
    public function index(Request $request)
    {
        // Ambil semua baris approved yang belum dibuatkan invoice,
        // lalu group by transaction_no di PHP agar 1 transaksi = 1 baris.
        $query = TransactionImportRow::with(['import'])
            ->whereIn('status', ['valid', 'anomaly'])
            ->where('is_approved', true)
            ->whereDoesntHave('invoice')
            // OTS = bayar on the spot, tidak perlu invoice
            ->whereDoesntHave('product', fn($q) => $q->where('payment_mode', 'OTS'))
            ->orderBy('date', 'asc');

        // Filter pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_no', 'like', "%{$search}%")
                  ->orWhere('ticket_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Ambil semua baris approved yang belum dibuatkan invoice
        $allRows = $query->get();

        // Cari tahu no_transaksi mana saja yang punya baris anomaly BELUM di-approve (di seluruh database)
        $unhandledTransactionNos = TransactionImportRow::whereIn('transaction_no', $allRows->pluck('transaction_no')->unique())
            ->where('status', 'anomaly')
            ->where('is_approved', false)
            ->distinct()
            ->pluck('transaction_no')
            ->toArray();

        $grouped = $allRows
            ->groupBy('transaction_no')
            ->map(function ($rows) use ($unhandledTransactionNos) {
                $first      = $rows->first();
                $totalGross = $rows->sum(fn($r) => ($r->publish_rate ?: $r->unit_price) * $r->qty);
                $totalNett  = $rows->sum(fn($r) => ($r->nett_price ?: $r->unit_price) * $r->qty);

                return (object) [
                    'transaction_no'  => $first->transaction_no,
                    'date'            => $first->date,
                    'import'          => $first->import,
                    'item_count'      => $rows->count(),
                    'total_amount'    => $totalGross,
                    'total_gross'     => $totalGross,
                    'total_nett'      => $totalNett,
                    'unit_gross'      => $first->publish_rate ?: $first->unit_price,
                    'unit_nett'       => $first->nett_price ?: $first->unit_price,
                    'qty'             => $rows->sum('qty'),
                    'total_komisi'    => $rows->sum('komisi_amount'),
                    'ticket_names'    => $rows->pluck('ticket_name')->filter()->unique()->implode(', '),
                    'has_unhandled'   => in_array($first->transaction_no, $unhandledTransactionNos),
                ];
            })
            ->values();

        // Manual pagination
        $perPage     = 30;
        $currentPage = $request->input('page', 1);
        $slice       = $grouped->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $grouped->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('pending-invoices.index', compact('transactions'));
    }

}
