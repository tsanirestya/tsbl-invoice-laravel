<?php
// TEMPORARY — DELETE THIS FILE after use.
if (($_GET['token'] ?? '') !== '95b994ecf5f6f0225be998f267e03dcd02b51f5fc363426ea8fd21e241247629') {
    http_response_code(403);
    exit('Forbidden');
}

$base = dirname(__DIR__);
$output = [];

// 1. Clear bootstrap cache (routes, config, events, services)
foreach (glob($base . '/bootstrap/cache/*.php') as $file) {
    $deleted = @unlink($file);
    $output[] = ($deleted ? 'Deleted' : 'Failed to delete') . ': ' . basename($file);
}

// 2. Run migrations
chdir($base);
$migrate = shell_exec('php artisan migrate --force 2>&1');
$output[] = "\n=== php artisan migrate --force ===\n" . ($migrate ?? '(shell_exec disabled)');

echo '<pre>' . implode("\n", $output) . '</pre>';
