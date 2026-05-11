<?php
/**
 * DB Reset Utility - Development Only
 * Clears transactional data while preserving master data.
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Mode web check (jika dijalankan via browser di server)
if (php_sapi_name() !== 'cli') {
    $token = $_GET['token'] ?? '';
    if ($token !== 'tsbl_reset_2026') {
        header('HTTP/1.0 403 Forbidden');
        exit('Forbidden');
    }
}

$tablesToTruncate = [
    'invoice_items',
    'invoices',
    'partner_deposits',
    'payment_allocations',
    'payment_memo_invoices',
    'payment_memos',
    'payments',
    'reconciliation_dsi_lines',
    'reconciliations',
    'reservations',
    'transaction_import_rows',
    'transaction_imports',
    // 'failed_jobs', // Opsional jika ada
    // 'jobs',        // Opsional jika ada
];

echo (php_sapi_name() === 'cli' ? "" : "<pre>");
echo "Starting Database Reset...\n";

try {
    // Disable foreign key constraints
    Schema::disableForeignKeyConstraints();

    foreach ($tablesToTruncate as $table) {
        if (Schema::hasTable($table)) {
            DB::table($table)->truncate();
            echo "Truncated: $table\n";
        } else {
            echo "Skipped (not found): $table\n";
        }
    }

    // Enable foreign key constraints
    Schema::enableForeignKeyConstraints();
    
    echo "\nSUCCESS: Transactional data has been cleared.\n";
    echo "Preserved: users, products, partners, settings.\n";

} catch (\Exception $e) {
    Schema::enableForeignKeyConstraints();
    echo "\nERROR: " . $e->getMessage() . "\n";
}

echo (php_sapi_name() === 'cli' ? "" : "</pre>");
