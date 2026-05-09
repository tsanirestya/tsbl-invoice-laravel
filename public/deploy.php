<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// TEMPORARY — DELETE THIS FILE after use.
if (($_GET['token'] ?? '') !== '95b994ecf5f6f0225be998f267e03dcd02b51f5fc363426ea8fd21e241247629') {
    http_response_code(403);
    exit('Forbidden');
}

$base = dirname(__DIR__);
$output = [];

// 1. Clear bootstrap cache
$cacheFiles = glob($base . '/bootstrap/cache/*.php') ?: [];
if (empty($cacheFiles)) {
    $output[] = 'No cache files found.';
} else {
    foreach ($cacheFiles as $file) {
        $deleted = @unlink($file);
        $output[] = ($deleted ? 'Deleted' : 'Failed') . ': ' . basename($file);
    }
}

// 2. Run migrations
$output[] = "\n=== migrate --force ===";
if (function_exists('shell_exec')) {
    chdir($base);
    $php = PHP_BINARY ?: 'php';
    $result = shell_exec($php . ' artisan migrate --force 2>&1');
    $output[] = $result ?? '(no output)';
} else {
    $output[] = 'shell_exec disabled on this host.';
}

echo '<pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';
