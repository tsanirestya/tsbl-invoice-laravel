<?php

namespace App\Http\Controllers;

use App\Models\DsiDuplicateFlag;
use App\Models\DsiImportBatch;
use App\Models\DsiTransaction;
use App\Services\DsiImporterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DsiController extends Controller
{
    public function __construct(
        private readonly DsiImporterService $importer
    ) {}

    /**
     * D3 — Upload CSV / receive API payload and run import.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file'   => 'required|file|mimes:csv,txt|max:10240',
            'source' => 'nullable|in:CSV,API',
        ]);

        $file     = $request->file('file');
        $source   = $request->input('source', 'CSV');
        $fileName = $file->getClientOriginalName();
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Parse CSV
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');
        $headers = null;

        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map('trim', $line);
                continue;
            }
            $row = array_combine($headers, $line);
            if ($row) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        if (empty($rows)) {
            return back()->with('error', 'CSV file is empty or has no data rows.');
        }

        try {
            $batch = $this->importer->import(
                rows:       $rows,
                source:     $source,
                fileName:   $fileName,
                fileHash:   $fileHash,
                importedBy: Auth::id()
            );

            return redirect()
                ->route('dsi.batches.show', $batch)
                ->with('success', "Import complete. Batch {$batch->batch_ref}: {$batch->processed_rows} rows processed, {$batch->failed_rows} failed.");

        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * D3 — Show the import upload form.
     */
    public function create()
    {
        return view('dsi.create');
    }

    /**
     * D3 — List all import batches with status.
     */
    public function batches(Request $request)
    {
        $query = DsiImportBatch::orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('batch_ref', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        $batches  = $query->paginate(20)->withQueryString();
        $statuses = ['PROCESSING', 'COMPLETED', 'PARTIAL', 'FAILED'];

        return view('dsi.batches.index', compact('batches', 'statuses'));
    }

    /**
     * D3 — Show a single import batch detail.
     */
    public function batchShow(DsiImportBatch $batch)
    {
        $batch->load(['transactions.duplicateFlags', 'transactions.lineItems']);
        return view('dsi.batches.show', compact('batch'));
    }

    /**
     * D3 — List flagged suspected duplicates awaiting review.
     */
    public function reviewDuplicate(Request $request)
    {
        $flags = DsiDuplicateFlag::with(['transaction.batch'])
            ->where('status', 'PENDING')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('dsi.duplicates.review', compact('flags'));
    }

    /**
     * D3 — Finance approves or rejects a suspected duplicate.
     * action: 'approve' (it IS a duplicate, reject transaction) | 'reject' (not a duplicate, allow it)
     */
    public function approveDuplicate(Request $request, DsiDuplicateFlag $flag)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes'  => 'nullable|string|max:1000',
        ]);

        if ($flag->status !== 'PENDING') {
            return back()->with('error', "Flag #{$flag->id} is already {$flag->status}.");
        }

        $newStatus = $validated['action'] === 'approve' ? 'CONFIRMED' : 'DISMISSED';

        $flag->update([
            'status'       => $newStatus,
            'reviewed_by'  => Auth::id(),
            'reviewed_at'  => now(),
            'review_notes' => $validated['notes'] ?? null,
        ]);

        if ($newStatus === 'CONFIRMED') {
            // Mark the flagged transaction as locked (cannot proceed to reconciliation)
            $flag->transaction?->update(['is_locked' => true]);
            $message = "Duplicate flag #{$flag->id} confirmed — transaction locked.";
        } else {
            $message = "Duplicate flag #{$flag->id} dismissed — transaction cleared for processing.";
        }

        return back()->with('success', $message);
    }
}
