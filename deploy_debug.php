<?php
/**
 * BUG-007 Troubleshooting Bridge
 * Used to clear cache and view logs on shared hosting without SSH.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$token = $_GET['token'] ?? '';
if ($token !== 'tsbl_debug_2026') {
    header('HTTP/1.0 403 Forbidden');
    exit('Forbidden');
}

$base = dirname(__DIR__) . '/tsbl-invoice-laravel';

if (!file_exists($base . '/vendor/autoload.php')) {
    exit("Error: Laravel root not found at $base. Check your directory structure.");
}

// Mode: log (default)
if (($_GET['mode'] ?? 'log') === 'log') {
    $logFile = $base . '/storage/logs/laravel.log';
    if (file_exists($logFile)) {
        $lines = array_slice(file($logFile), -150);
        echo "<h3>Latest 150 Log Lines:</h3><pre>" . htmlspecialchars(implode('', $lines)) . "</pre>";
    } else {
        echo "Log file not found at $logFile";
    }
    exit;
}

// Mode: fix (clear cache)
define('LARAVEL_START', microtime(true));
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h3>Artisan Command Output:</h3><pre>";
foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $cmd) {
    try {
        \Illuminate\Support\Facades\Artisan::call($cmd);
        echo "$cmd: " . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output()) . "\n";
    } catch (\Exception $e) {
        echo "$cmd FAILED: " . $e->getMessage() . "\n";
    }
}
echo "</pre><h4>Done. Try refreshing the main site now.</h4>";
