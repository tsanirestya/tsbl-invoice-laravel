<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$appRoot = realpath(__DIR__ . '/../tsbl-invoice-laravel');

echo '<pre>';
echo 'PHP version: ' . PHP_VERSION . "\n";
echo 'appRoot: ' . $appRoot . "\n\n";

// Try loading autoloader
try {
    require $appRoot . '/vendor/autoload.php';
    echo "✅ Autoloader loaded\n";
} catch (Throwable $e) {
    echo "❌ Autoloader error: " . $e->getMessage() . "\n";
    exit;
}

// Try bootstrapping app
try {
    $app = require_once $appRoot . '/bootstrap/app.php';
    echo "✅ App bootstrapped\n";
} catch (Throwable $e) {
    echo "❌ Bootstrap error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
    exit;
}

// Try kernel
try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "✅ Kernel bootstrapped\n";
    echo "APP_ENV: " . env('APP_ENV') . "\n";
    echo "DB_DATABASE: " . env('DB_DATABASE') . "\n";
} catch (Throwable $e) {
    echo "❌ Kernel error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo '</pre>';
