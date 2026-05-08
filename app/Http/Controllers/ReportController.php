<?php

namespace App\Http\Controllers;

use App\Models\CreditClass;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\PartnerDeposit;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\TransactionImport;
use App\Models\TransactionImportRow;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

        $summary        = $this->buildSummary($request);
        $partners       = Partner::where('is_active', 1)->orderBy('nama_partner')->get(['id', 'nama_partner']);
        $partnerSummary = $this->buildPartnerSummary($request);
        $depositReport  = $this->buildDepositReport();
        $importSummary  = $this->buildImportSummary();
        $creditClasses  = CreditClass::orderBy('sort_order')->get(['id', 'name', 'color']);
        $creditSummary  = $this->buildCreditSummary($request);
        $creditAging    = $this->buildCreditAging();

        return view('reports.index', compact(
            'invoices', 'summary', 'partners', 'partnerSummary',
            'depositReport', 'importSummary',
            'creditClasses', 'creditSummary', 'creditAging'
        ));
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
        $base       = $this->buildQuery($request);
        $invoiceIds = (clone $base)->pluck('id');

        $cashMasuk       = (float) Payment::whereIn('invoice_id', $invoiceIds)->sum('amount');
        $depositDipakai  = (float) PartnerDeposit::whereIn('invoice_id', $invoiceIds)
                                ->where('type', 'DEDUCTION')
                                ->sum('amount');

        return [
            'total_invoice'     => (clone $base)->count(),
            'total_revenue'     => (clone $base)->where('payment_status', 'PAID')->sum('grand_total'),
            'total_outstanding' => (clone $base)->whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])->sum('grand_total'),
            'total_overdue'     => (clone $base)->where('payment_status', 'OVERDUE')->sum('grand_total'),
            'count_paid'        => (clone $base)->where('payment_status', 'PAID')->count(),
            'count_unpaid'      => (clone $base)->where('payment_status', 'UNPAID')->count(),
            'count_partial'     => (clone $base)->where('payment_status', 'PARTIAL')->count(),
            'count_overdue'     => (clone $base)->where('payment_status', 'OVERDUE')->count(),
            'cash_masuk'        => $cashMasuk,
            'deposit_dipakai'   => $depositDipakai,
            'total_uang_masuk'  => $cashMasuk + $depositDipakai,
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

    private function buildDepositReport(): \Illuminate\Support\Collection
    {
        return PartnerDeposit::select(
                'partner_id',
                DB::raw("SUM(CASE WHEN type='TOPUP' THEN amount ELSE 0 END) as total_topup"),
                DB::raw("SUM(CASE WHEN type='DEDUCTION' THEN amount ELSE 0 END) as total_deduction"),
                DB::raw("SUM(CASE WHEN type='TOPUP' THEN amount WHEN type='DEDUCTION' THEN -amount WHEN type='ADJUSTMENT' THEN amount ELSE 0 END) as balance")
            )
            ->with('partner:id,nama_partner')
            ->groupBy('partner_id')
            ->orderByDesc('balance')
            ->get();
    }

    public function exportAnomalyExcel(TransactionImport $import)
    {
        $rows = $import->rows()->where('status', 'anomaly')->with('anomalies', 'product')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Anomaly Report');

        // Headers
        $headers = ['Row #', 'Trx No', 'Tanggal', 'Ticket Type', 'Ticket Name', 'Unit Price', 'Qty',
                    'Anomaly Types', 'Detail', 'Match Method', 'Status'];
        foreach ($headers as $col => $h) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $h);
        }

        // Data
        $r = 2;
        foreach ($rows as $row) {
            $types  = $row->anomalies->pluck('anomaly_type')->implode(', ');
            $detail = $row->anomalies->pluck('detail')->implode(' | ');
            $sheet->setCellValueByColumnAndRow(1, $r, $row->row_index);
            $sheet->setCellValueByColumnAndRow(2, $r, $row->transaction_no);
            $sheet->setCellValueByColumnAndRow(3, $r, $row->date?->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow(4, $r, $row->ticket_type);
            $sheet->setCellValueByColumnAndRow(5, $r, $row->ticket_name);
            $sheet->setCellValueByColumnAndRow(6, $r, $row->unit_price);
            $sheet->setCellValueByColumnAndRow(7, $r, $row->qty);
            $sheet->setCellValueByColumnAndRow(8, $r, $types);
            $sheet->setCellValueByColumnAndRow(9, $r, $detail);
            $sheet->setCellValueByColumnAndRow(10, $r, $row->match_method);
            $sheet->setCellValueByColumnAndRow(11, $r, $row->is_approved ? 'Approved' : 'Pending');
            $r++;
        }

        $filename = 'anomaly-' . $import->id . '-' . now()->format('Ymd') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildImportSummary(): array
    {
        $rows = TransactionImportRow::where('status', 'valid')
            ->whereNotNull('matched_product_id')
            ->with('product:id,product_name')
            ->get();

        $byType = $rows->groupBy('ticket_type')->map(fn($g) => [
            'count'       => $g->count(),
            'total_amount'=> $g->sum('total_amount'),
            'total_komisi'=> $g->sum('komisi_amount'),
        ]);

        $byNationality = [
            'local'   => $rows->where('nationality', '!=', null)
                              ->filter(fn($r) => strtolower($r->nationality ?? '') === 'indonesia' || strtolower($r->country ?? '') === 'indonesia')
                              ->count(),
            'foreign' => $rows->filter(fn($r) => strtolower($r->nationality ?? '') !== 'indonesia' && strtolower($r->country ?? '') !== 'indonesia' && ($r->nationality || $r->country))
                              ->count(),
        ];

        $topProducts = $rows->groupBy('ticket_name')
            ->map(fn($g) => ['name' => $g->first()->ticket_name, 'count' => $g->count()])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $totalImports = TransactionImport::count();
        $doneImports  = TransactionImport::where('status', 'done')->count();
        $totalKomisi  = $rows->sum('komisi_amount');

        return compact('byType', 'byNationality', 'topProducts', 'totalImports', 'doneImports', 'totalKomisi');
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

    private function buildCreditSummary(Request $request): \Illuminate\Support\Collection
    {
        $query = Partner::creditPartners()->with('creditClass');

        if ($request->filled('credit_class_id')) {
            $query->where('credit_class_id', $request->credit_class_id);
        }

        $partners = $query->orderBy('nama_partner')->get();

        $rows = $partners->map(function ($partner) {
            $info = $partner->creditInfo();
            return (object) [
                'partner'             => $partner,
                'limit'               => $info['limit'],
                'used'                => $info['used'],
                'available'           => $info['available'],
                'utilization_percent' => $info['utilization_percent'],
                'status'              => $info['status'],
                'credit_class_name'   => $info['credit_class_name'],
                'credit_class_color'  => $info['credit_class_color'],
            ];
        });

        if ($request->filled('credit_status')) {
            $rows = $rows->filter(fn($r) => $r->status === $request->credit_status);
        }

        return $rows->sortByDesc('utilization_percent')->values();
    }

    private function buildCreditAging(): array
    {
        $b1 = (int) Setting::get('credit_aging_bucket_1', 30);
        $b2 = (int) Setting::get('credit_aging_bucket_2', 60);
        $b3 = (int) Setting::get('credit_aging_bucket_3', 90);
        $b4 = (int) Setting::get('credit_aging_bucket_4', 120);

        $today = now()->startOfDay();

        $invoices = Invoice::whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])
            ->whereHas('partner', fn($q) => $q->where('limit_credit', '>', 0))
            ->with(['partner.creditClass', 'payments'])
            ->get();

        $byPartner = $invoices->groupBy('partner_id');

        $rows = [];
        foreach ($byPartner as $partnerInvoices) {
            $partner = $partnerInvoices->first()->partner;
            $buckets = ['current' => 0, 'b1' => 0, 'b2' => 0, 'b3' => 0, 'b4' => 0, 'b5' => 0];

            foreach ($partnerInvoices as $inv) {
                $outstanding = max(0, $inv->grand_total - $inv->payments->sum('amount'));
                if ($outstanding <= 0) continue;

                if (!$inv->due_date) {
                    $buckets['current'] += $outstanding;
                    continue;
                }

                $diff        = $today->diffInDays($inv->due_date, false);
                $daysOverdue = $diff < 0 ? abs($diff) : 0;

                if ($daysOverdue === 0) {
                    $buckets['current'] += $outstanding;
                } elseif ($daysOverdue <= $b1) {
                    $buckets['b1'] += $outstanding;
                } elseif ($daysOverdue <= $b2) {
                    $buckets['b2'] += $outstanding;
                } elseif ($daysOverdue <= $b3) {
                    $buckets['b3'] += $outstanding;
                } elseif ($daysOverdue <= $b4) {
                    $buckets['b4'] += $outstanding;
                } else {
                    $buckets['b5'] += $outstanding;
                }
            }

            $rows[] = (object) [
                'partner' => $partner,
                'buckets' => $buckets,
                'total'   => array_sum($buckets),
            ];
        }

        usort($rows, fn($a, $b) => $b->total <=> $a->total);

        $totals = ['current' => 0, 'b1' => 0, 'b2' => 0, 'b3' => 0, 'b4' => 0, 'b5' => 0];
        foreach ($rows as $row) {
            foreach ($totals as $key => $_) {
                $totals[$key] += $row->buckets[$key];
            }
        }

        return [
            'rows'    => $rows,
            'buckets' => compact('b1', 'b2', 'b3', 'b4'),
            'totals'  => $totals,
        ];
    }

    public function exportCreditCsv(Request $request)
    {
        $creditSummary = $this->buildCreditSummary($request);
        $creditAging   = $this->buildCreditAging();

        $filename = 'laporan-kredit-' . now()->format('Ymd-His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($creditSummary, $creditAging) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Section 1: Credit Summary
            fputcsv($out, ['=== CREDIT SUMMARY ===']);
            fputcsv($out, ['Partner', 'Credit Class', 'Limit', 'Used', 'Available', 'Utilization %', 'Status']);
            foreach ($creditSummary as $row) {
                fputcsv($out, [
                    $row->partner->nama_partner,
                    $row->credit_class_name ?? '-',
                    number_format($row->limit, 2, '.', ''),
                    number_format($row->used, 2, '.', ''),
                    number_format($row->available, 2, '.', ''),
                    number_format($row->utilization_percent, 2, '.', ''),
                    $row->status,
                ]);
            }

            fputcsv($out, []);

            // Section 2: Credit Aging
            $b  = $creditAging['buckets'];
            $b1 = $b['b1']; $b2 = $b['b2']; $b3 = $b['b3']; $b4 = $b['b4'];
            fputcsv($out, ['=== CREDIT AGING ===']);
            fputcsv($out, [
                'Partner', 'Credit Class',
                'Current', "1–{$b1} hari", "{$b1}+1–{$b2} hari", "{$b2}+1–{$b3} hari",
                "{$b3}+1–{$b4} hari", ">{$b4} hari", 'Total',
            ]);
            foreach ($creditAging['rows'] as $row) {
                $bk = $row->buckets;
                fputcsv($out, [
                    $row->partner->nama_partner,
                    $row->partner->creditClass->name ?? '-',
                    number_format($bk['current'], 2, '.', ''),
                    number_format($bk['b1'], 2, '.', ''),
                    number_format($bk['b2'], 2, '.', ''),
                    number_format($bk['b3'], 2, '.', ''),
                    number_format($bk['b4'], 2, '.', ''),
                    number_format($bk['b5'], 2, '.', ''),
                    number_format($row->total, 2, '.', ''),
                ]);
            }

            // Totals row
            $t = $creditAging['totals'];
            fputcsv($out, [
                'TOTAL', '',
                number_format($t['current'], 2, '.', ''),
                number_format($t['b1'], 2, '.', ''),
                number_format($t['b2'], 2, '.', ''),
                number_format($t['b3'], 2, '.', ''),
                number_format($t['b4'], 2, '.', ''),
                number_format($t['b5'], 2, '.', ''),
                number_format(array_sum($t), 2, '.', ''),
            ]);

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportCreditPdf(Request $request)
    {
        $creditSummary = $this->buildCreditSummary($request);
        $creditAging   = $this->buildCreditAging();
        $settings      = Setting::all()->pluck('value', 'key');

        $pdf = Pdf::loadView('reports.credit-pdf', compact('creditSummary', 'creditAging', 'settings'))
            ->setPaper('a4', 'landscape');

        $filename = 'laporan-kredit-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($filename);
    }
}
