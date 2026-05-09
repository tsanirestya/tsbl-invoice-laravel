<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (($_GET['token'] ?? '') !== '95b994ecf5f6f0225be998f267e03dcd02b51f5fc363426ea8fd21e241247629') {
    http_response_code(403);
    exit('Forbidden');
}

$base = dirname(__DIR__) . '/tsbl-invoice-laravel';

define('LARAVEL_START', microtime(true));
require $base . '/vendor/autoload.php';

$app = require_once $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '<pre>';

// Clear stale view cache
\Illuminate\Support\Facades\Artisan::call('view:clear');
echo "view:clear\n" . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output());

// Clear route cache
\Illuminate\Support\Facades\Artisan::call('route:clear');
echo "route:clear\n" . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output());

// Clear config cache
\Illuminate\Support\Facades\Artisan::call('config:clear');
echo "config:clear\n" . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output());

// Run migrations
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo "migrate --force\n" . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output());

echo "\nDone. Test /login now.\n";
echo '</pre>';
