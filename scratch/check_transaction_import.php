<?php

use App\Models\TransactionImportRow;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transactionNo = '498731';
$rows = TransactionImportRow::where('transaction_no', $transactionNo)->get();

foreach ($rows as $row) {
    echo "ID: {$row->id} | Import ID: {$row->import_id} | Status: {$row->status} | Approved: " . ($row->is_approved ? 'YES' : 'NO') . " | Ticket: {$row->ticket_name}\n";
}
