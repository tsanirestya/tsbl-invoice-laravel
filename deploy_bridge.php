<?php
/**
 * Deployment Bridge - Unzip Utility
 * Used to extract deployment packages uploaded via FTP.
 */

// Set timezone to match expected runner time or be consistent
date_default_timezone_set('UTC');

// Basic security - token check
$token = $_GET['token'] ?? '';
$expectedToken = 'tsbl_deploy_'.date('Ymd');

if ($token !== $expectedToken) {
    header('HTTP/1.0 403 Forbidden');
    echo "ERROR: Invalid deployment token. Expected token for date ".date('Ymd')." UTC.";
    exit;
}

if (!class_exists('ZipArchive')) {
    header('HTTP/1.0 500 Internal Server Error');
    echo "ERROR: ZipArchive extension is not installed on this server.";
    exit;
}

$target = $_GET['target'] ?? ''; // 'app' or 'web'
$zipFile = ($target === 'app') ? __DIR__ . '/../tsbl-invoice-laravel/app.zip' : __DIR__ . '/web.zip';
$extractTo = ($target === 'app') ? __DIR__ . '/../tsbl-invoice-laravel/' : __DIR__ . '/public/';

if ($target === 'web' && !file_exists(__DIR__ . '/public/')) {
    if (!mkdir(__DIR__ . '/public/', 0755, true)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo "ERROR: Failed to create public directory.";
        exit;
    }
}

if (!file_exists($zipFile)) {
    header('HTTP/1.0 404 Not Found');
    echo "ERROR: Zip file not found at $zipFile. Please check if FTP upload succeeded.";
    exit;
}

$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    if ($zip->extractTo($extractTo)) {
        $zip->close();
        unlink($zipFile);
        echo "SUCCESS: $target extracted successfully.";
    } else {
        header('HTTP/1.0 500 Internal Server Error');
        echo "ERROR: Failed to extract zip file to $extractTo. Check permissions.";
    }
} else {
    header('HTTP/1.0 500 Internal Server Error');
    echo "ERROR: Failed to open zip file $target at $zipFile.";
}

