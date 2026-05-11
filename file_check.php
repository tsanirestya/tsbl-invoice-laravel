<?php
echo "<h3>Current Directory: " . __DIR__ . "</h3>";
echo "<h3>Files in this directory:</h3><pre>";
$files = scandir(__DIR__);
foreach ($files as $file) {
    $size = is_file($file) ? filesize($file) . " bytes" : "[DIR]";
    echo str_pad($file, 30) . " $size\n";
}
echo "</pre>";

$appDir = __DIR__ . '/../tsbl-invoice-laravel';
echo "<h3>Files in ../tsbl-invoice-laravel:</h3><pre>";
if (file_exists($appDir)) {
    $files = scandir($appDir);
    foreach ($files as $file) {
        $size = is_file($appDir . '/' . $file) ? filesize($appDir . '/' . $file) . " bytes" : "[DIR]";
        echo str_pad($file, 30) . " $size\n";
    }
} else {
    echo "Directory not found: $appDir";
}
echo "</pre>";
