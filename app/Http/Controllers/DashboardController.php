<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Partner;
use App\Models\PartnerDeposit;
use App\Models\TransactionImport;
use App\Models\TransactionImportRow;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        Invoice::where('payment_status', '!=', 'PAID')
            ->where('due_date', '<', $today)
            ->update(['payment_status' => 'OVERDUE']);

        $stats = [
            'total'       => Invoice::count(),
            'unpaid'      => Invoice::where('payment_status', 'UNPAID')->count(),
            'partial'     => Invoice::where('payment_status', 'PARTIAL')->count(),
            'paid'        => Invoice::where('payment_status', 'PAID')->count(),
            'overdue'     => Invoice::where('payment_status', 'OVERDUE')->count(),
            'revenue'     => Invoice::where('payment_status', 'PAID')->sum('grand_total'),
            'outstanding' => Invoice::whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])->sum('grand_total'),
        ];

        // Antrian invoice belum dibuat — oldest first
        $pendingQueue = TransactionImportRow::with(['import'])
            ->whereIn('status', ['valid', 'anomaly'])
            ->where('is_approved', true)
            ->whereDoesntHave('invoice')
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('transaction_no')
            ->map(function ($rows) {
                $first = $rows->first();
                return (object) [
                    'transaction_no' => $first->transaction_no,
                    'date'           => $first->date,
                    'import'         => $first->import,
                    'item_count'     => $rows->count(),
                    'total_amount'   => $rows->sum('total_amount'),
                    'ticket_names'   => $rows->pluck('ticket_name')->filter()->unique()->implode(', '),
                ];
            })
            ->take(10)
            ->values();

        $pendingCount = TransactionImportRow::whereIn('status', ['valid', 'anomaly'])
            ->where('is_approved', true)
            ->whereDoesntHave('invoice')
            ->count();

        // Invoice mendekati due date — belum lunas, urut due_date ASC
        $dueSoonInvoices = Invoice::with('partner')
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])
            ->whereNotNull('due_date')
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        $totalPartners = Partner::where('is_active', true)->count();

        // Deposit metrics
        $depositSaldoTotal = (float) PartnerDeposit::selectRaw(
            "SUM(CASE WHEN type='TOPUP' THEN amount WHEN type='DEDUCTION' THEN -amount WHEN type='ADJUSTMENT' THEN amount ELSE 0 END) as saldo"
        )->value('saldo');

        $depositMetrics = [
            'saldo_total' => $depositSaldoTotal,
        ];

        $LOW_THRESHOLD = 5_000_000; // Rp 5 juta

        $activePartners   = Partner::where('is_active', true)->orderBy('nama_partner')->get(['id', 'nama_partner']);

        // Full deposit list per partner — only show those with balance > 0, sorted ascending
        $partnerDeposits = $activePartners->map(function ($p) {
            $balance = $p->depositBalance();
            return [
                'id'        => $p->id,
                'name'      => $p->nama_partner,
                'balance'   => $balance,
                'topup_url' => route('deposit-invoices.create', ['partner_id' => $p->id]),
            ];
        })->filter(fn($p) => $p['balance'] > 0)->sortBy('balance')->values();

        // Alert: partners with 0 < balance < Rp 5 juta
        $lowDepositAlert = $partnerDeposits->filter(fn($p) => $p['balance'] < $LOW_THRESHOLD)->values();

        // Import metrics
        $pendingImports  = TransactionImport::whereIn('status', ['pending', 'processing', 'reviewed'])->count();
        $pendingAnomalies = TransactionImport::with('rows')
            ->whereIn('status', ['pending', 'processing', 'reviewed'])
            ->get()
            ->sum(fn($i) => $i->pendingAnomalies());

        $latestImport = TransactionImport::latest()->first();
        $highAnomalyAlert = $latestImport && $latestImport->anomalyRate() > 20;

        return view('dashboard.index', compact(
            'stats', 'totalPartners',
            'depositMetrics', 'partnerDeposits', 'lowDepositAlert',
            'pendingImports', 'pendingAnomalies', 'highAnomalyAlert', 'latestImport',
            'pendingQueue', 'pendingCount', 'dueSoonInvoices'
        ));
    }
}
