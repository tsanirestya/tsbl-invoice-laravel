<?php
/**
 * Deployment Bridge - Unzip Utility
 * Used to extract deployment packages uploaded via FTP.
 */

// Set timezone to match expected runner time or be consistent
date_default_timezone_set('UTC');
set_time_limit(0);
ignore_user_abort(true);

// Basic security - token check
$key = $_GET['key'] ?? '';
$expectedKey = 'tsbl_deploy_'.date('Ymd');

// Debug log to see what's happening
file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " Received: [$key], Expected: [$expectedKey]\n", FILE_APPEND);

if ($key !== $expectedKey) {
    header('HTTP/1.0 403 Forbidden');
    echo "ERROR: Invalid deployment key. Check deploy_log.txt on server.";
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
$openResult = $zip->open($zipFile);
if ($openResult === TRUE) {
    file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " Successfully opened $zipFile for $target.\n", FILE_APPEND);
    
    // Attempt extraction
    if ($zip->extractTo($extractTo)) {
        $zip->close();
        unlink($zipFile);
        file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " SUCCESS: $target extracted to $extractTo.\n", FILE_APPEND);
        echo "SUCCESS: $target extracted successfully.";
    } else {
        $error = error_get_last();
        file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " ERROR: Failed to extract $target to $extractTo. Error: " . ($error['message'] ?? 'Unknown PHP error') . "\n", FILE_APPEND);
        header('HTTP/1.0 500 Internal Server Error');
        echo "ERROR: Failed to extract zip file to $extractTo. Check permissions.";
    }
} else {
    file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " ERROR: Failed to open zip file $target at $zipFile. Code: $openResult\n", FILE_APPEND);
    header('HTTP/1.0 500 Internal Server Error');
    echo "ERROR: Failed to open zip file $target at $zipFile. Code: $openResult";
}


