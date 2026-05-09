<?php

namespace App\Http\Controllers;

use App\Models\TransactionImport;
use App\Services\ImportPipelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransactionImportController extends Controller
{
    public function index()
    {
        $imports = TransactionImport::with('uploader')
            ->latest()
            ->paginate(20);

        return view('imports.index', compact('imports'));
    }

    public function create()
    {
        return view('imports.create');
    }

    public function store(Request $request, ImportPipelineService $pipeline)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $file     = $request->file('file');
        $origName = $file->getClientOriginalName();
        $stored   = $file->storeAs('imports', Str::uuid() . '.' . $file->extension());

        $import = TransactionImport::create([
            'uuid'              => (string) Str::uuid(),
            'filename'          => $stored,
            'original_filename' => $origName,
            'uploaded_by'       => auth()->id(),
            'uploaded_at'       => now(),
            'status'            => 'pending',
        ]);

        try {
            $pipeline->run($import, Storage::path($stored));
        } catch (\Throwable $e) {
            $import->update(['status' => 'pending']);
            return back()->withErrors(['file' => 'Pipeline error: ' . $e->getMessage()]);
        }

        $import->refresh();

        return redirect()->route('imports.show', $import)
            ->with('success', "Import selesai: {$import->valid_rows} valid, {$import->anomaly_rows} anomaly, {$import->rejected_rows} rejected.");
    }

    public function show(TransactionImport $import)
    {
        $import->load([
            'rows.anomalies',
            'rows.product',
            'rejections',
            'uploader',
        ]);

        $validRows   = $import->rows->where('status', 'valid');
        $anomalyRows = $import->rows->where('status', 'anomaly');
        $rejections  = $import->rejections;

        $totalKomisi = $validRows->sum('komisi_amount') + $anomalyRows->where('is_approved', true)->sum('komisi_amount');

        // Summary badge: anomaly type → total anomaly records
        $anomalyTypes = $anomalyRows->flatMap->anomalies->groupBy('anomaly_type');

        // Grouped view: primary_anomaly_type → ticket_name → group data
        // Priority: errors first (SUSPICIOUS_PRICING > PRICE_MISMATCH), then warnings
        $typePriority = [
            'SUSPICIOUS_PRICING' => 0,
            'PRICE_MISMATCH'     => 1,
            'PRODUCT_NOT_FOUND'  => 2,
            'CATEGORY_MISMATCH'  => 3,
            'REVERSE_MISMATCH'   => 4,
            'FUZZY_CANDIDATE'    => 5,
        ];

        // Build groups: each row's primary type = lowest priority number among its anomalies
        $anomalyGroups = [];
        foreach ($anomalyRows as $row) {
            $primaryType = $row->anomalies
                ->sortBy(fn($a) => $typePriority[$a->anomaly_type] ?? 99)
                ->first()
                ?->anomaly_type ?? 'UNKNOWN';

            $key = $row->ticket_name ?? '(unknown)';

            if (!isset($anomalyGroups[$primaryType][$key])) {
                $anomalyGroups[$primaryType][$key] = [
                    'ticket_name'   => $key,
                    'rows'          => collect(),
                    'total'         => 0,
                    'pending'       => 0,
                    'pending_ids'   => [],
                    'all_ids'       => [],
                    'sample_row'    => $row,      // for displaying anomaly detail
                ];
            }

            $anomalyGroups[$primaryType][$key]['rows']->push($row);
            $anomalyGroups[$primaryType][$key]['total']++;
            $anomalyGroups[$primaryType][$key]['all_ids'][] = $row->id;

            if (!$row->is_approved) {
                $anomalyGroups[$primaryType][$key]['pending']++;
                $anomalyGroups[$primaryType][$key]['pending_ids'][] = $row->id;
            }
        }

        // Sort anomaly types by priority
        uksort($anomalyGroups, fn($a, $b) => ($typePriority[$a] ?? 99) <=> ($typePriority[$b] ?? 99));

        return view('imports.show', compact(
            'import', 'validRows', 'anomalyRows', 'rejections',
            'anomalyTypes', 'anomalyGroups', 'totalKomisi'
        ));
    }

    public function destroy(TransactionImport $import)
    {
        if ($import->status === 'done') {
            return back()->withErrors(['delete' => 'Import yang sudah selesai tidak bisa dihapus.']);
        }

        Storage::delete($import->filename);
        $import->delete();

        return redirect()->route('imports.index')->with('success', 'Import dihapus.');
    }
}
