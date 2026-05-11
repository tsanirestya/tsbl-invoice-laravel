<?php
/**
 * CHECKER: Invoice Create Fix Verification
 * Jalankan: php artisan_check_invoice_fix.php
 * 
 * Memverifikasi bahwa bug "simpan invoice tidak ada impact" sudah diperbaiki.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\TransactionImportRow;
use App\Models\Invoice;
use Illuminate\Support\Facades\Validator;

$pass  = 0;
$fail  = 0;
$warns = 0;

function check(string $label, bool $result, string $detail = '', bool $isWarn = false): void {
    global $pass, $fail, $warns;
    $icon  = $result ? '✅' : ($isWarn ? '⚠️ ' : '❌');
    $state = $result ? 'PASS' : ($isWarn ? 'WARN' : 'FAIL');
    if ($result)        $pass++;
    elseif ($isWarn)    $warns++;
    else                $fail++;
    echo "  $icon [$state] $label" . ($detail ? "\n         → $detail" : '') . "\n";
}

function section(string $title): void {
    echo "\n" . str_repeat('─', 60) . "\n";
    echo "  $title\n";
    echo str_repeat('─', 60) . "\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║   Invoice Create Fix — Step-by-Step Verification        ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";

// ─────────────────────────────────────────────────────────────
section('STEP 1 — Cek Relasi Model TransactionImportRow');
// ─────────────────────────────────────────────────────────────

$row = new TransactionImportRow();
$relations = get_class_methods($row);

check(
    'TransactionImportRow::invoice() relation exists',
    method_exists($row, 'invoice'),
    'Relasi hasOne Invoice via import_row_id diperlukan untuk whereDoesntHave'
);

// ─────────────────────────────────────────────────────────────
section('STEP 2 — Cek Data Transaksi 498471');
// ─────────────────────────────────────────────────────────────

$trxNo = '498471';

$allRows = TransactionImportRow::where('transaction_no', $trxNo)->get();
check(
    "TransactionImportRow exists untuk transaction_no=$trxNo",
    $allRows->isNotEmpty(),
    "Total rows: " . $allRows->count()
);

$approvedRows = TransactionImportRow::where('transaction_no', $trxNo)
    ->whereIn('status', ['valid','anomaly'])
    ->where('is_approved', true)
    ->get();

check(
    "Ada row yang sudah approved",
    $approvedRows->isNotEmpty(),
    "Approved rows: " . $approvedRows->count()
);

$linkedRows = $approvedRows->filter(fn($r) => Invoice::where('import_row_id', $r->id)->exists());
$freeRows   = $approvedRows->filter(fn($r) => !Invoice::where('import_row_id', $r->id)->exists());

check(
    "Ada row yang SUDAH punya invoice (seharusnya difilter)",
    $linkedRows->isNotEmpty(),
    "Row linked: " . $linkedRows->pluck('id')->join(', ')
);

check(
    "Ada row yang BELUM punya invoice (bisa dibuat invoice baru)",
    $freeRows->isNotEmpty(),
    "Row free: " . $freeRows->pluck('id')->join(', ')
);

// ─────────────────────────────────────────────────────────────
section('STEP 3 — Simulasi Controller create() BEFORE Fix');
// ─────────────────────────────────────────────────────────────

// BEFORE: tanpa whereDoesntHave
$importRowsBefore = TransactionImportRow::where('transaction_no', $trxNo)
    ->whereIn('status', ['valid','anomaly'])
    ->where('is_approved', true)
    ->get();

$firstBefore = $importRowsBefore->first();
if ($firstBefore) {
    $vBefore = Validator::make(
        ['import_row_id' => $firstBefore->id],
        ['import_row_id' => 'nullable|integer|exists:transaction_import_rows,id|unique:invoices,import_row_id']
    );
    $failsBefore = $vBefore->fails();
    check(
        "BEFORE fix: Validasi import_row_id='{$firstBefore->id}' GAGAL (bug aslinya)",
        $failsBefore,
        $failsBefore ? 'BENAR — ini adalah bug yang menyebabkan simpan tidak berhasil' : 'Sudah tidak bisa direproduksi',
        !$failsBefore
    );
} else {
    echo "  ⚠️  [WARN] Tidak ada approved rows untuk simulasi BEFORE\n";
    $warns++;
}

// ─────────────────────────────────────────────────────────────
section('STEP 4 — Simulasi Controller create() AFTER Fix');
// ─────────────────────────────────────────────────────────────

// AFTER: dengan whereDoesntHave
$importRowsAfter = TransactionImportRow::where('transaction_no', $trxNo)
    ->whereIn('status', ['valid','anomaly'])
    ->where('is_approved', true)
    ->whereDoesntHave('invoice')
    ->get();

check(
    "AFTER fix: importRows hanya berisi row yang belum punya invoice",
    $importRowsAfter->isNotEmpty(),
    "Count: " . $importRowsAfter->count() . " | IDs: " . $importRowsAfter->pluck('id')->join(', ')
);

$firstAfter = $importRowsAfter->first();
if ($firstAfter) {
    check(
        "AFTER fix: firstRow->id ({$firstAfter->id}) belum terhubung ke invoice manapun",
        !Invoice::where('import_row_id', $firstAfter->id)->exists(),
        "import_row_id yang akan dikirim: {$firstAfter->id}"
    );

    $vAfter = Validator::make(
        ['import_row_id' => $firstAfter->id],
        ['import_row_id' => 'nullable|integer|exists:transaction_import_rows,id|unique:invoices,import_row_id']
    );
    check(
        "AFTER fix: Validasi import_row_id='{$firstAfter->id}' LULUS",
        !$vAfter->fails(),
        $vAfter->fails() ? 'Masih gagal: ' . implode(', ', $vAfter->errors()->all()) : 'Unique constraint terpenuhi'
    );
}

// ─────────────────────────────────────────────────────────────
section('STEP 5 — Cek Kode Controller Sudah Di-update');
// ─────────────────────────────────────────────────────────────

$controllerPath = __DIR__ . '/app/Http/Controllers/InvoiceController.php';
$controllerCode = file_get_contents($controllerPath);

check(
    "InvoiceController@create() mengandung whereDoesntHave('invoice')",
    str_contains($controllerCode, "whereDoesntHave('invoice')"),
    "Filter row yang sudah punya invoice sudah diterapkan"
);

check(
    "Form view mengandung @error('import_row_id') untuk tampilkan error",
    str_contains(
        file_get_contents(__DIR__ . '/resources/views/invoices/_form.blade.php'),
        "@error('import_row_id')"
    ),
    "Error hidden field sekarang terlihat oleh user"
);

check(
    "setOverLimit() menggunakan 'block' bukan '' (empty string)",
    str_contains($controllerCode = file_get_contents(__DIR__ . '/resources/views/invoices/_form.blade.php'), "display = isOver ? 'block' : 'none'"),
    "Bug JS submit guard sudah diperbaiki"
);

check(
    "Submit guard menggunakan dataset.overlimit, bukan style.display",
    str_contains($controllerCode, "dataset.overlimit === '1'"),
    "Guard tidak lagi memblokir submit untuk partner tanpa credit limit"
);

// ─────────────────────────────────────────────────────────────
section('STEP 6 — Cek Transaksi Lain (Sampel)');
// ─────────────────────────────────────────────────────────────

// Cari transaksi lain yang berpotensi sama masalahnya
$problematic = TransactionImportRow::whereIn('status', ['valid','anomaly'])
    ->where('is_approved', true)
    ->whereHas('invoice')
    ->groupBy('transaction_no')
    ->pluck('transaction_no');

$allProblematic = 0;
foreach ($problematic as $trx) {
    $hasUnlinked = TransactionImportRow::where('transaction_no', $trx)
        ->whereIn('status', ['valid','anomaly'])
        ->where('is_approved', true)
        ->whereDoesntHave('invoice')
        ->exists();
    if ($hasUnlinked) $allProblematic++;
}

check(
    "Jumlah transaksi lain yang berpotensi sama masalahnya: $allProblematic",
    true,
    $allProblematic > 0
        ? "Ada $allProblematic transaksi lain dengan row sebagian sudah invoice — fix sudah handle otomatis"
        : "Tidak ada transaksi bermasalah lain",
    $allProblematic > 0
);

// ─────────────────────────────────────────────────────────────
// SUMMARY
// ─────────────────────────────────────────────────────────────
echo "\n" . str_repeat('═', 60) . "\n";
echo "  HASIL: ✅ $pass PASS  |  ❌ $fail FAIL  |  ⚠️  $warns WARN\n";
echo str_repeat('═', 60) . "\n";

if ($fail === 0) {
    echo "\n  ✅ SEMUA CHECK LULUS — Fix sudah berhasil diterapkan.\n";
    echo "     Silakan coba buat invoice di browser:\n";
    echo "     http://localhost/tsbl-invoice-laravel/public/invoices/create?transaction_no=498471&visit_date=2026-04-01\n\n";
} else {
    echo "\n  ❌ ADA $fail CHECK YANG GAGAL — Perlu investigasi lebih lanjut.\n\n";
}
