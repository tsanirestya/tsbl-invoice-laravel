<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        Invoice::syncOverdueStatuses();

        $query = $this->buildQuery($request);

        $invoices = (clone $query)->with('partner')->orderByDesc('invoice_date')->orderByDesc('id')->paginate(50)->withQueryString();

        $summary = $this->buildSummary($request);
        $partners = Partner::where('is_active', 1)->orderBy('nama_partner')->get(['id', 'nama_partner']);
        $partnerSummary = $this->buildPartnerSummary($request);

        return view('reports.index', compact('invoices', 'summary', 'partners', 'partnerSummary'));
    }

    public function exportCsv(Request $request)
    {
        Invoice::syncOverdueStatuses();

        $invoices = $this->buildQuery($request)->with('partner')->orderByDesc('invoice_date')->get();

        $filename = 'laporan-invoice-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($invoices) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel

            fputcsv($out, [
                'No Invoice', 'Partner', 'Tamu', 'Tgl Invoice', 'Jatuh Tempo',
                'Subtotal', 'Deposit', 'Grand Total', 'Total Dibayar', 'Sisa',
                'Status', 'Finalized',
            ]);

            foreach ($invoices as $inv) {
                fputcsv($out, [
                    $inv->invoice_no,
                    $inv->partner->nama_partner ?? '-',
                    $inv->guest_name ?? '-',
                    $inv->invoice_date?->format('d/m/Y') ?? '-',
                    $inv->due_date?->format('d/m/Y') ?? '-',
                    number_format($inv->subtotal, 2, '.', ''),
                    number_format($inv->deposit, 2, '.', ''),
                    number_format($inv->grand_total, 2, '.', ''),
                    number_format($inv->totalPaid(), 2, '.', ''),
                    number_format(max(0, $inv->grand_total - $inv->totalPaid()), 2, '.', ''),
                    $inv->payment_status,
                    $inv->is_finalized ? 'Ya' : 'Tidak',
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        Invoice::syncOverdueStatuses();

        $invoices = $this->buildQuery($request)->with('partner')->orderByDesc('invoice_date')->get();
        $summary  = $this->buildSummary($request);
        $settings = Setting::all()->pluck('value', 'key');
        $filters  = $this->getFilterLabels($request);

        $pdf = Pdf::loadView('reports.pdf', compact('invoices', 'summary', 'settings', 'filters'))
            ->setPaper('a4', 'landscape');

        $filename = 'laporan-invoice-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($filename);
    }

    // --- helpers ---

    private function buildQuery(Request $request)
    {
        $query = Invoice::query();

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

        if ($request->filled('finalized')) {
            $query->where('is_finalized', $request->finalized);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_no', 'like', "%{$s}%")
                  ->orWhere('guest_name', 'like', "%{$s}%")
                  ->orWhereHas('partner', fn($p) => $p->where('nama_partner', 'like', "%{$s}%"));
            });
        }

        return $query;
    }

    private function buildSummary(Request $request): array
    {
        $base = $this->buildQuery($request);

        return [
            'total_invoice'   => (clone $base)->count(),
            'total_revenue'   => (clone $base)->where('payment_status', 'PAID')->sum('grand_total'),
            'total_outstanding' => (clone $base)->whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])->sum('grand_total'),
            'total_overdue'   => (clone $base)->where('payment_status', 'OVERDUE')->sum('grand_total'),
            'count_paid'      => (clone $base)->where('payment_status', 'PAID')->count(),
            'count_unpaid'    => (clone $base)->where('payment_status', 'UNPAID')->count(),
            'count_partial'   => (clone $base)->where('payment_status', 'PARTIAL')->count(),
            'count_overdue'   => (clone $base)->where('payment_status', 'OVERDUE')->count(),
        ];
    }

    private function buildPartnerSummary(Request $request): \Illuminate\Support\Collection
    {
        return $this->buildQuery($request)
            ->select('partner_id',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(grand_total) as total_billed'),
                DB::raw('SUM(CASE WHEN payment_status = "PAID" THEN grand_total ELSE 0 END) as total_paid'),
                DB::raw('SUM(CASE WHEN payment_status IN ("UNPAID","PARTIAL","OVERDUE") THEN grand_total ELSE 0 END) as total_outstanding')
            )
            ->with('partner:id,nama_partner')
            ->groupBy('partner_id')
            ->orderByDesc('total_billed')
            ->get();
    }

    private function getFilterLabels(Request $request): array
    {
        $labels = [];
        if ($request->filled('status')) $labels[] = 'Status: ' . $request->status;
        if ($request->filled('date_from')) $labels[] = 'Dari: ' . $request->date_from;
        if ($request->filled('date_to')) $labels[] = 'S/d: ' . $request->date_to;
        if ($request->filled('partner_id')) {
            $p = Partner::find($request->partner_id);
            if ($p) $labels[] = 'Partner: ' . $p->nama_partner;
        }
        return $labels;
    }
}
