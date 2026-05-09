<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (($_GET['token'] ?? '') !== '95b994ecf5f6f0225be998f267e03dcd02b51f5fc363426ea8fd21e241247629') {
    http_response_code(403);
    exit('Forbidden');
}

$base = dirname(__DIR__) . '/tsbl-invoice-laravel';
$logFile = $base . '/storage/logs/laravel.log';

echo '<pre>';
if (!file_exists($logFile)) {
    echo "Log file not found: $logFile\n";
} else {
    // Read last 100 lines
    $lines = file($logFile);
    $last = array_slice($lines, -100);
    echo htmlspecialchars(implode('', $last));
}
echo '</pre>';
