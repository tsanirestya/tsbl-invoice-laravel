<?php

use App\Models\TransactionImportRow;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transactionNo = '498731';
$rows = TransactionImportRow::where('transaction_no', $transactionNo)->get();

foreach ($rows as $row) {
    echo "ID: {$row->id} | Qty: {$row->qty} | Price: {$row->unit_price} | Total: {$row->total_amount} | Ticket: {$row->ticket_name}\n";
}
