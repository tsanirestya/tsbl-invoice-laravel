<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$appRoot = realpath(__DIR__ . '/../tsbl-invoice-laravel');

require $appRoot . '/vendor/autoload.php';
$app = require_once $appRoot . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '<pre>';

// Config values (from cache)
echo "config('app.env')      = " . config('app.env') . "\n";
echo "config('app.url')      = " . config('app.url') . "\n";
echo "config('database.connections.mysql.database') = " . config('database.connections.mysql.database') . "\n\n";

// Test DB connection
try {
    $pdo = DB::connection()->getPdo();
    echo "✅ DB connected: " . DB::connection()->getDatabaseName() . "\n";
} catch (Throwable $e) {
    echo "❌ DB error: " . $e->getMessage() . "\n";
}

echo '</pre>';
