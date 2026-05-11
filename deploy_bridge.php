<?php
/**
 * Deployment Bridge - Unzip Utility
 * Used to extract deployment packages uploaded via FTP.
 */

// Basic security - you can add a token check here if needed
$token = $_GET['token'] ?? '';
$expectedToken = 'tsbl_deploy_'.date('Ymd');

if ($token !== $expectedToken) {
    header('HTTP/1.0 403 Forbidden');
    echo "Invalid deployment token.";
    exit;
}

$target = $_GET['target'] ?? ''; // 'app' or 'web'
$zipFile = ($target === 'app') ? __DIR__ . '/../tsbl-invoice-laravel/app.zip' : __DIR__ . '/web.zip';
$extractTo = ($target === 'app') ? __DIR__ . '/../tsbl-invoice-laravel/' : __DIR__ . '/public/';

if ($target === 'web' && !file_exists(__DIR__ . '/public/')) {
    mkdir(__DIR__ . '/public/', 0755, true);
}

if (!file_exists($zipFile)) {
    echo "Error: Zip file not found at $zipFile";
    exit;
}

$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    $zip->extractTo($extractTo);
    $zip->close();
    unlink($zipFile);
    echo "SUCCESS: $target extracted successfully.";
} else {
    echo "ERROR: Failed to open zip file $target.";
}
