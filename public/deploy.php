<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// TEMPORARY — DELETE THIS FILE after use.
if (($_GET['token'] ?? '') !== '95b994ecf5f6f0225be998f267e03dcd02b51f5fc363426ea8fd21e241247629') {
    http_response_code(403);
    exit('Forbidden');
}

echo '<pre>';
echo '__DIR__: ' . __DIR__ . "\n\n";

// List current dir
echo "=== Contents of __DIR__ ===\n";
foreach (scandir(__DIR__) as $f) echo "  $f\n";

// List parent
$parent = dirname(__DIR__);
echo "\n=== Contents of dirname(__DIR__) ($parent) ===\n";
foreach (scandir($parent) as $f) echo "  $f\n";

// Search artisan up to 5 levels up
echo "\n=== Searching for artisan + vendor ===\n";
$dir = __DIR__;
for ($i = 0; $i < 6; $i++) {
    $hasArtisan = file_exists($dir . '/artisan');
    $hasVendor  = file_exists($dir . '/vendor/autoload.php');
    echo "$dir — artisan:" . ($hasArtisan ? 'YES' : 'no') . " vendor:" . ($hasVendor ? 'YES' : 'no') . "\n";
    $dir = dirname($dir);
}
echo '</pre>';
