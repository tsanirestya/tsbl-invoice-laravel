<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// TEMPORARY — DELETE THIS FILE after use.
if (($_GET['token'] ?? '') !== '95b994ecf5f6f0225be998f267e03dcd02b51f5fc363426ea8fd21e241247629') {
    http_response_code(403);
    exit('Forbidden');
}

// Webroot is /home2/transen2/invoice.transentertainment.id/
// Laravel root is /home2/transen2/tsbl-invoice-laravel/
$base = dirname(__DIR__) . '/tsbl-invoice-laravel';

echo '<pre>';
echo "Laravel base: $base\n";
echo "vendor exists: " . (file_exists($base . '/vendor/autoload.php') ? 'YES' : 'NO') . "\n";
echo "artisan exists: " . (file_exists($base . '/artisan') ? 'YES' : 'NO') . "\n\n";

if (!file_exists($base . '/vendor/autoload.php')) {
    echo "ERROR: vendor/autoload.php not found at $base/vendor/\n";
    echo "vendor/ is excluded from FTP deploy — must be uploaded manually.\n";
    echo '</pre>';
    exit;
}

define('LARAVEL_START', microtime(true));
require $base . '/vendor/autoload.php';

$app = require_once $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== php artisan migrate --force ===\n";
$exitCode = \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo htmlspecialchars(\Illuminate\Support\Facades\Artisan::output());
echo "Exit code: $exitCode\n";
echo '</pre>';
