<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

// Disable output buffering so we get real-time progress
if (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);

echo "<h2>Manual Extraction Tool</h2>";

$zipFile = __DIR__ . '/../tsbl-invoice-laravel/app.zip';
$extractTo = __DIR__ . '/../tsbl-invoice-laravel/';

echo "Source: $zipFile<br>";
echo "Target: $extractTo<br>";
flush();

if (!file_exists($zipFile)) {
    die("ERROR: Zip file not found.");
}

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive;
    $res = $zip->open($zipFile);
    if ($res === TRUE) {
        echo "Zip opened via ZipArchive successfully. Starting extraction...<br>";
        flush();
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
    echo "ZipArchive not available. Trying SimpleZipExtractor (Pure PHP)...<br>";
    flush();
    require_once 'simple_unzip.php';
    try {
        if (SimpleZipExtractor::extract($zipFile, $extractTo)) {
            echo "<b style='color:green'>SUCCESS: Extraction complete via SimpleZipExtractor.</b><br>";
        } else {
            echo "<b style='color:red'>ERROR: SimpleZipExtractor failed.</b><br>";
        }
    } catch (Exception $e) {
        echo "<b style='color:red'>ERROR: " . $e->getMessage() . "</b><br>";
    }
}


