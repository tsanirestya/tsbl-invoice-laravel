<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// TEMPORARY — DELETE THIS FILE after use.
if (($_GET['token'] ?? '') !== '95b994ecf5f6f0225be998f267e03dcd02b51f5fc363426ea8fd21e241247629') {
    http_response_code(403);
    exit('Forbidden');
}

// Auto-detect Laravel root (works whether deploy.php lands in root or public/)
$candidates = [__DIR__, dirname(__DIR__)];
$base = null;
foreach ($candidates as $path) {
    if (file_exists($path . '/vendor/autoload.php')) {
        $base = $path;
        break;
    }
}

if (!$base) {
    die('<pre>ERROR: vendor/autoload.php not found. Checked: ' . implode(', ', $candidates) . '</pre>');
}

echo '<pre>Base: ' . $base . "\n\n";

define('LARAVEL_START', microtime(true));
require $base . '/vendor/autoload.php';

$app = require_once $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$exitCode = \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo 'Exit code: ' . $exitCode . "\n";
echo htmlspecialchars(\Illuminate\Support\Facades\Artisan::output());
echo '</pre>';
