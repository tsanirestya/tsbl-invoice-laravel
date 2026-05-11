<?php

use App\Models\TransactionImportRow;
use App\Models\TransactionImport;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orphanCount = TransactionImportRow::whereDoesntHave('import')->count();
echo "Orphan rows count: $orphanCount\n";

$orphans = TransactionImportRow::whereDoesntHave('import')->get();
foreach ($orphans as $row) {
    echo "ID: {$row->id} | Import ID: {$row->import_id} | Trx: {$row->transaction_no}\n";
}
