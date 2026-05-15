<?php
date_default_timezone_set('UTC');
echo "<h3>Server Environment</h3>";
echo "Current Time (UTC): " . date('Y-m-d H:i:s') . "<br>";
echo "Current Date (Ymd): " . date('Ymd') . "<br>";
echo "Expected Token: tsbl_deploy_" . date('Ymd') . "<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "ZipArchive: " . (class_exists('ZipArchive') ? "Enabled" : "DISABLED") . "<br>";

echo "<h3>Current Directory: " . __DIR__ . "</h3>";
echo "<h3>Files in this directory:</h3><pre>";
$files = scandir(__DIR__);
foreach ($files as $file) {
    $size = is_file(__DIR__ . '/' . $file) ? filesize(__DIR__ . '/' . $file) . " bytes" : "[DIR]";
    echo str_pad($file, 30) . " $size\n";
}
echo "</pre>";

$appDir = __DIR__ . '/../tsbl-invoice-laravel';
echo "<h3>Files in ../tsbl-invoice-laravel:</h3><pre>";
if (file_exists($appDir)) {
    $files = scandir($appDir);
    foreach ($files as $file) {
        $filePath = $appDir . '/' . $file;
        $size = is_file($filePath) ? filesize($filePath) . " bytes" : "[DIR]";
        echo str_pad($file, 30) . " $size\n";
    }
} else {
    echo "Directory not found: $appDir";
}
echo "</pre>";

