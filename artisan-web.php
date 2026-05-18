<?php
/**
 * Web Artisan Runner — production deploy helper
 *
 * Access: /artisan-web.php?token=YOUR_DEPLOY_TOKEN
 * Protected by DEPLOY_TOKEN in .env — remove or set to empty to disable entirely.
 * DELETE this file after deployment is done if you want zero exposure.
 */

// ── Bootstrap Laravel ──────────────────────────────────────────────────────
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// ── Token guard ────────────────────────────────────────────────────────────
$deployToken = env('DEPLOY_TOKEN', '');
$inputToken  = $_REQUEST['token'] ?? '';

if ($deployToken === '' || $inputToken !== $deployToken) {
    http_response_code(403);
    die('403 Forbidden — DEPLOY_TOKEN mismatch or not set.');
}

// ── Whitelist ──────────────────────────────────────────────────────────────
$allowed = [
    'migrate'                => ['--force'],
    'migrate:status'         => [],
    'cache:clear'            => [],
    'config:clear'           => [],
    'config:cache'           => [],
    'route:clear'            => [],
    'route:cache'            => [],
    'view:clear'             => [],
    'optimize'               => [],
    'optimize:clear'         => [],
    'storage:link'           => [],
    'queue:restart'          => [],
    'schedule:run'           => [],
];

// ── Run ────────────────────────────────────────────────────────────────────
$cmd    = $_REQUEST['cmd'] ?? '';
$output = '';
$status = null;

if ($cmd !== '' && isset($allowed[$cmd])) {
    $extraArgs = $allowed[$cmd];
    $artisanArgs = [];
    foreach ($extraArgs as $flag) {
        $artisanArgs[$flag] = true;
    }
    $status = \Illuminate\Support\Facades\Artisan::call($cmd, $artisanArgs);
    $output = \Illuminate\Support\Facades\Artisan::output();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Artisan Web Runner — TSBL</title>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body { margin: 0; font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 2rem 1rem; }
  .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; width: 100%; max-width: 680px; overflow: hidden; }
  .card-header { background: linear-gradient(135deg, #1d4ed8, #1e40af); padding: 1.25rem 1.5rem; }
  .card-header h1 { margin: 0; font-size: 1rem; font-weight: 700; color: #fff; }
  .card-header p  { margin: .25rem 0 0; font-size: .8rem; color: #bfdbfe; }
  .card-body  { padding: 1.5rem; }
  label { font-size: .78rem; font-weight: 600; color: #94a3b8; display: block; margin-bottom: .4rem; }
  .btn-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .5rem; margin-top: .75rem; }
  .btn { padding: .55rem .75rem; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #cbd5e1; font-size: .8rem; font-weight: 600; cursor: pointer; text-align: left; transition: background .15s, border-color .15s; display: flex; align-items: center; gap: .5rem; width: 100%; }
  .btn:hover { background: #1e3a5f; border-color: #3b82f6; color: #93c5fd; }
  .btn-danger { border-color: #7f1d1d; color: #fca5a5; }
  .btn-danger:hover { background: #450a0a; border-color: #ef4444; }
  .output-wrap { margin-top: 1.25rem; }
  .output-label { font-size: .72rem; font-weight: 700; color: #64748b; letter-spacing: 1px; text-transform: uppercase; margin-bottom: .4rem; }
  pre { background: #020617; border: 1px solid #1e293b; border-radius: 8px; padding: 1rem; font-size: .8rem; line-height: 1.6; color: #86efac; overflow-x: auto; white-space: pre-wrap; word-break: break-all; min-height: 80px; }
  .badge { display: inline-block; padding: .2rem .6rem; border-radius: 99px; font-size: .7rem; font-weight: 700; }
  .badge-ok  { background: #14532d; color: #86efac; }
  .badge-err { background: #450a0a; color: #fca5a5; }
  .warn { background: #431407; border: 1px solid #7c2d12; color: #fed7aa; border-radius: 8px; padding: .75rem 1rem; font-size: .8rem; margin-bottom: 1rem; }
  form { margin: 0; }
</style>
</head>
<body>
<div class="card">
  <div class="card-header">
    <h1>⚙ Artisan Web Runner</h1>
    <p>TSBL Invoice — Production Deploy Helper</p>
  </div>
  <div class="card-body">

    <div class="warn">
      ⚠ Hapus atau nonaktifkan file ini setelah deployment selesai.
      Set <code>DEPLOY_TOKEN=</code> (kosong) di .env untuk menonaktifkan.
    </div>

    <label>Pilih perintah yang akan dijalankan:</label>
    <div class="btn-grid">
      <?php foreach (array_keys($allowed) as $c): ?>
        <form method="POST">
          <input type="hidden" name="token" value="<?= htmlspecialchars($inputToken) ?>">
          <input type="hidden" name="cmd"   value="<?= htmlspecialchars($c) ?>">
          <button type="submit" class="btn <?= str_contains($c, 'migrate') ? 'btn-danger' : '' ?>">
            <?= str_contains($c, 'migrate') ? '⚠' : '▶' ?>
            <?= htmlspecialchars($c) ?>
          </button>
        </form>
      <?php endforeach; ?>
    </div>

    <?php if ($cmd !== ''): ?>
    <div class="output-wrap">
      <div class="output-label">
        Output — <code><?= htmlspecialchars($cmd) ?></code>
        &nbsp;
        <span class="badge <?= $status === 0 ? 'badge-ok' : 'badge-err' ?>">
          <?= $status === 0 ? 'OK' : 'Exit ' . $status ?>
        </span>
      </div>
      <pre><?= htmlspecialchars(trim($output) ?: '(no output)') ?></pre>
    </div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
