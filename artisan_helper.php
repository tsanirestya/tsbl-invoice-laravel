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

// Security Authorization Check
$key = $_GET['key'] ?? '';
$expectedKey = 'tsbl_deploy_' . date('Ymd');
if ($key !== $expectedKey) {
    header('HTTP/1.0 403 Forbidden');
    die("<h3>403 Forbidden: Unauthorized Access</h3>");
}

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
if (isset($_GET['show_log'])) {
    $logPath = __DIR__ . '/../tsbl-invoice-laravel/storage/logs/laravel.log';
    if (file_exists($logPath)) {
        $content = file_get_contents($logPath);
        // Split by "[20" (the beginning of log timestamps)
        $parts = explode("\n[20", $content);
        $lastParts = array_slice($parts, -3);
        echo "<h3>Latest 3 Laravel Errors:</h3>";
        foreach ($lastParts as $part) {
            $formattedPart = trim($part);
            if (!str_starts_with($formattedPart, '[')) {
                $formattedPart = "[20" . $formattedPart;
            }
            // Show only the first 25 lines of stack trace for each error so it doesn't clutter the page
            $lines = explode("\n", $formattedPart);
            $shortLines = array_slice($lines, 0, 30);
            $shortContent = implode("\n", $shortLines);
            if (count($lines) > 30) {
                $shortContent .= "\n... (truncated " . (count($lines) - 30) . " lines)";
            }
            echo "<pre style='background:#f8f9fa; border:1px solid #ccc; padding:10px; margin-bottom:15px; max-height: 400px; overflow: auto;'>" . htmlspecialchars($shortContent) . "</pre>";
        }
    } else {
        echo "Log file not found at: " . $logPath;
    }
} elseif ($cmd) {
    runArtisan($cmd);
} else {
    echo "Usage: artisan_helper.php?key=tsbl_deploy_YYYYMMDD&cmd=migrate<br>";
    echo "Common commands: <br>";
    echo "<li><a href='?key=$key&cmd=migrate --force'>migrate --force</a></li>";
    echo "<li><a href='?key=$key&cmd=migrate:status'>migrate:status</a></li>";
    echo "<li><a href='?key=$key&cmd=view:clear'>view:clear</a></li>";
    echo "<li><a href='?key=$key&cmd=cache:clear'>cache:clear</a></li>";
    echo "<li><a href='?key=$key&cmd=route:clear'>route:clear</a></li>";
    echo "<li><a href='?key=$key&cmd=config:clear'>config:clear</a></li>";
    echo "<li><a href='?key=$key&show_log=1'>show_log</a></li>";
}

