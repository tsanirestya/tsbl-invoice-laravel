<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security Authorization Check
$key = $_GET['key'] ?? '';
$expectedKey = 'tsbl_deploy_' . date('Ymd');
if ($key !== $expectedKey) {
    header('HTTP/1.0 403 Forbidden');
    die("Unauthorized Access.");
}

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Bootstrap console kernel
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "<h2>Transaction Imports Debugger</h2>";

echo "<h3>Latest 5 Imports:</h3>";
try {
    $imports = DB::table('transaction_imports')->orderBy('id', 'desc')->limit(5)->get();
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Filename</th><th>Original Name</th><th>Status</th><th>Total</th><th>Valid</th><th>Anomaly</th><th>Rejected</th><th>Processed At</th></tr>";
    foreach ($imports as $imp) {
        echo "<tr>";
        echo "<td>{$imp->id}</td>";
        echo "<td>{$imp->filename}</td>";
        echo "<td>{$imp->original_filename}</td>";
        echo "<td><strong>{$imp->status}</strong></td>";
        echo "<td>{$imp->total_rows}</td>";
        echo "<td>{$imp->valid_rows}</td>";
        echo "<td>{$imp->anomaly_rows}</td>";
        echo "<td>{$imp->rejected_rows}</td>";
        echo "<td>{$imp->processed_at}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (\Throwable $e) {
    echo "Error fetching imports: " . $e->getMessage() . "<br>";
}

echo "<h3>Latest 5 Rejections:</h3>";
try {
    $rejections = DB::table('import_rejections')->orderBy('id', 'desc')->limit(5)->get();
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Import ID</th><th>Row Index</th><th>Rejection Reason</th><th>Raw Data</th></tr>";
    foreach ($rejections as $rej) {
        echo "<tr>";
        echo "<td>{$rej->id}</td>";
        echo "<td>{$rej->import_id}</td>";
        echo "<td>{$rej->row_index}</td>";
        echo "<td><strong style='color:red;'>{$rej->rejection_reason}</strong></td>";
        echo "<td><pre>" . htmlspecialchars($rej->raw_data) . "</pre></td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (\Throwable $e) {
    echo "Error fetching rejections: " . $e->getMessage() . "<br>";
}
