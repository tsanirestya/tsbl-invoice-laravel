<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Artisan Helper (Artisan::call)</h2>";

// Bootstrap Laravel
require __DIR__.'/../tsbl-invoice-laravel/vendor/autoload.php';
$app = require_once __DIR__.'/../tsbl-invoice-laravel/bootstrap/app.php';

use Illuminate\Support\Facades\Artisan;

// Resolve console kernel to enable Artisan
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function runArtisan($command) {
    echo "Running: php artisan $command...<br>";
    try {
        $status = Artisan::call($command);
        $output = Artisan::output();
        echo "Status Code: $status<br>";
        echo "<pre>$output</pre><hr>";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
}

$cmd = $_GET['cmd'] ?? '';
if ($cmd) {
    runArtisan($cmd);
} else {
    echo "Usage: artisan_helper.php?cmd=migrate<br>";
    echo "Common commands: <br>";
    echo "<li><a href='?cmd=migrate --force'>migrate --force</a></li>";
    echo "<li><a href='?cmd=migrate:status'>migrate:status</a></li>";
    echo "<li><a href='?cmd=view:clear'>view:clear</a></li>";
    echo "<li><a href='?cmd=cache:clear'>cache:clear</a></li>";
    echo "<li><a href='?cmd=route:clear'>route:clear</a></li>";
    echo "<li><a href='?cmd=config:clear'>config:clear</a></li>";
}

