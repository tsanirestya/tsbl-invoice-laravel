<?php

use App\Models\TransactionImportRow;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transactionNo = '498731';

$rows = TransactionImportRow::where('transaction_no', $transactionNo)->get();

if ($rows->isEmpty()) {
    echo "No rows found for transaction no: $transactionNo\n";
} else {
    foreach ($rows as $row) {
        echo "ID: " . $row->id . "\n";
        echo "Transaction No: " . $row->transaction_no . "\n";
        echo "Status: " . $row->status . "\n";
        echo "Is Approved: " . ($row->is_approved ? 'YES' : 'NO') . "\n";
        echo "Approved At: " . $row->approved_at . "\n";
        echo "Approved By: " . $row->approved_by . "\n";
        echo "Override Reason: " . $row->override_reason . "\n";
        echo "Has Invoice: " . ($row->invoice ? 'YES (ID: ' . $row->invoice->id . ')' : 'NO') . "\n";
        echo "--------------------------\n";
    }
}
