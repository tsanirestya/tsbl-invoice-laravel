<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    DB::connection()->getPdo();
    echo "Connected successfully to " . DB::connection()->getDatabaseName();
} catch (\Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
