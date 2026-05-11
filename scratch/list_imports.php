<?php

use App\Models\TransactionImport;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$imports = TransactionImport::all();

foreach ($imports as $import) {
    echo "Import ID: {$import->id} | File: {$import->file_name} | Date: {$import->created_at}\n";
}
