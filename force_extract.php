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

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive;
    $res = $zip->open($zipFile);
    if ($res === TRUE) {
        echo "Zip opened via ZipArchive successfully. Starting extraction...<br>";
        if ($zip->extractTo($extractTo)) {
            echo "<b style='color:green'>SUCCESS: Extraction complete via ZipArchive.</b><br>";
            $zip->close();
        } else {
            echo "<b style='color:red'>ERROR: Extraction failed via ZipArchive.</b><br>";
        }
    } else {
        echo "<b style='color:red'>ERROR: Could not open zip via ZipArchive. Code: $res</b><br>";
    }
} else {
    echo "ZipArchive not found. Trying shell unzip...<br>";
    $command = "unzip -o " . escapeshellarg($zipFile) . " -d " . escapeshellarg($extractTo) . " 2>&1";
    $output = shell_exec($command);
    if ($output === null) {
        echo "<b style='color:red'>ERROR: shell_exec is disabled or failed.</b><br>";
    } else {
        echo "Shell output: <pre>$output</pre>";
        if (strpos($output, 'inflating') !== false || strpos($output, 'extracting') !== false) {
            echo "<b style='color:green'>SUCCESS: Extraction likely complete via shell unzip.</b><br>";
        } else {
            echo "<b style='color:red'>ERROR: shell unzip might have failed.</b><br>";
        }
    }
}

