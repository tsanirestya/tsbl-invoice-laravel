<?php

use App\Models\TransactionImportRow;
use App\Models\ImportRejection;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rowOrphans = TransactionImportRow::whereDoesntHave('import')->delete();
$rejOrphans = ImportRejection::whereDoesntHave('import')->delete();

echo "Cleaned up $rowOrphans orphan rows and $rejOrphans orphan rejections.\n";
