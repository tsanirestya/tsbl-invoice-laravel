<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

echo "<h2>Manual Extraction Tool</h2>";

$zipFile = __DIR__ . '/../tsbl-invoice-laravel/app.zip';
$extractTo = __DIR__ . '/../tsbl-invoice-laravel/';

echo "Source: $zipFile<br>";
echo "Target: $extractTo<br>";

if (!file_exists($zipFile)) {
    die("ERROR: Zip file not found.");
}

if (!class_exists('ZipArchive')) {
    die("ERROR: ZipArchive not found.");
}

$zip = new ZipArchive;
$res = $zip->open($zipFile);
if ($res === TRUE) {
    echo "Zip opened successfully. Starting extraction...<br>";
    if ($zip->extractTo($extractTo)) {
        echo "<b style='color:green'>SUCCESS: Extraction complete.</b><br>";
        $zip->close();
        // unlink($zipFile); // Keep it for now just in case
    } else {
        echo "<b style='color:red'>ERROR: Extraction failed.</b><br>";
        $err = error_get_last();
        echo "Reason: " . ($err['message'] ?? 'Unknown');
    }
} else {
    echo "<b style='color:red'>ERROR: Could not open zip. Code: $res</b>";
}
