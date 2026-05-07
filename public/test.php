<?php
$appRoot = __DIR__ . '/../tsbl-invoice-laravel';

$checks = [
    'vendor/autoload.php'   => $appRoot . '/vendor/autoload.php',
    '.env'                  => $appRoot . '/.env',
    'bootstrap/app.php'     => $appRoot . '/bootstrap/app.php',
    'storage/ writable'     => is_writable($appRoot . '/storage'),
    'bootstrap/cache/ writable' => is_writable($appRoot . '/bootstrap/cache'),
];

echo '<pre>';
echo '__DIR__ = ' . __DIR__ . "\n";
echo 'appRoot = ' . $appRoot . "\n\n";

foreach ($checks as $label => $path) {
    if (is_bool($path)) {
        echo ($path ? '✅' : '❌') . " $label\n";
    } else {
        echo (file_exists($path) ? '✅' : '❌') . " $label => $path\n";
    }
}
echo '</pre>';
