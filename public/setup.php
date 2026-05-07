<?php
/**
 * One-time server setup script.
 * RUN ONCE then DELETE this file via cPanel File Manager.
 * Access: https://invoice.transentertainment.id/setup.php?token=TSBL_SETUP_2026
 */

define('SETUP_TOKEN', 'TSBL_SETUP_2026');

if (!isset($_GET['token']) || $_GET['token'] !== SETUP_TOKEN) {
    http_response_code(403);
    die('Forbidden.');
}

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$results = [];

// 1. Run migrations
try {
    $exitCode = Artisan::call('migrate', ['--force' => true]);
    $results['migrate'] = $exitCode === 0
        ? ['status' => 'OK', 'output' => Artisan::output()]
        : ['status' => 'ERROR', 'output' => Artisan::output()];
} catch (Exception $e) {
    $results['migrate'] = ['status' => 'EXCEPTION', 'output' => $e->getMessage()];
}

// 2. Storage link
try {
    // Remove existing symlink if broken
    $linkPath = public_path('storage');
    if (is_link($linkPath)) {
        unlink($linkPath);
    }
    $exitCode = Artisan::call('storage:link');
    $results['storage_link'] = $exitCode === 0
        ? ['status' => 'OK', 'output' => Artisan::output()]
        : ['status' => 'ERROR', 'output' => Artisan::output()];
} catch (Exception $e) {
    $results['storage_link'] = ['status' => 'EXCEPTION', 'output' => $e->getMessage()];
}

// 3. Clear & re-cache
try {
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:cache');
    $results['cache'] = ['status' => 'OK', 'output' => 'Config, route, view cached.'];
} catch (Exception $e) {
    $results['cache'] = ['status' => 'EXCEPTION', 'output' => $e->getMessage()];
}

// 4. Check .env loaded correctly
$results['env_check'] = [
    'APP_ENV'    => env('APP_ENV'),
    'DB_DATABASE'=> env('DB_DATABASE'),
    'APP_URL'    => env('APP_URL'),
];

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<title>TSBL Setup</title>
<style>
  body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 2rem; }
  h1 { color: #4ec9b0; }
  .ok { color: #4ec9b0; }
  .error { color: #f44747; }
  .exception { color: #ff8c00; }
  pre { background: #252526; padding: 1rem; border-radius: 4px; white-space: pre-wrap; }
  .warning { background: #5c3a1e; border: 1px solid #ff8c00; padding: 1rem; border-radius: 4px; margin-top: 2rem; }
</style>
</head>
<body>
<h1>TSBL Invoice — Server Setup</h1>
<?php foreach ($results as $step => $result): ?>
  <h2><?= htmlspecialchars(strtoupper($step)) ?></h2>
  <?php if (is_array($result) && isset($result['status'])): ?>
    <p class="<?= strtolower($result['status']) ?>"><?= $result['status'] ?></p>
    <pre><?= htmlspecialchars($result['output'] ?? '') ?></pre>
  <?php else: ?>
    <pre><?= htmlspecialchars(print_r($result, true)) ?></pre>
  <?php endif; ?>
<?php endforeach; ?>
<div class="warning">
  ⚠️ DELETE this file immediately after setup!<br>
  Via cPanel File Manager: <code>public_html/invoice.transentertainment.id/public/setup.php</code>
</div>
</body>
</html>
