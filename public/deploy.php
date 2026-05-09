<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// TEMPORARY — DELETE THIS FILE after use.
if (($_GET['token'] ?? '') !== '95b994ecf5f6f0225be998f267e03dcd02b51f5fc363426ea8fd21e241247629') {
    http_response_code(403);
    exit('Forbidden');
}

define('LARAVEL_START', microtime(true));

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require_once dirname(__DIR__) . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ob_start();
$exitCode = Artisan::call('migrate', ['--force' => true]);
$output = ob_get_clean();

echo '<pre>';
echo 'Exit code: ' . $exitCode . "\n\n";
echo htmlspecialchars(Artisan::output());
echo '</pre>';
