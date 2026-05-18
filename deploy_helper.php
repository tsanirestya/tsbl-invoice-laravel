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

// Check for ZipArchive
$hasZipArchive = class_exists('ZipArchive');

if (!$hasZipArchive) {
    file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " ZipArchive missing. Fallback to shell unzip.\n", FILE_APPEND);
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

if ($hasZipArchive) {
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
} else {
    // Fallback to shell unzip (safely check if available and not disabled)
    $output = null;
    $shellSuccess = false;
    try {
        if (function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
            $command = "unzip -o " . escapeshellarg($zipFile) . " -d " . escapeshellarg($extractTo) . " 2>&1";
            $output = @shell_exec($command);
            if ($output !== null && (strpos($output, 'inflating') !== false || strpos($output, 'extracting') !== false)) {
                $shellSuccess = true;
            }
        }
    } catch (Throwable $e) {
        file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " Shell unzip exception: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    if ($shellSuccess) {
        unlink($zipFile);
        file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " SUCCESS: $target extracted via shell.\n", FILE_APPEND);
        echo "SUCCESS: $target extracted via shell.";
    } else {
        // Fallback to SimpleZipExtractor (Pure PHP streaming unzip)
        file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " Attempting SimpleZipExtractor for $target.\n", FILE_APPEND);
        require_once 'simple_unzip.php';
        try {
            if (SimpleZipExtractor::extract($zipFile, $extractTo)) {
                unlink($zipFile);
                file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " SUCCESS: $target extracted via SimpleZipExtractor.\n", FILE_APPEND);
                echo "SUCCESS: $target extracted via SimpleZipExtractor.";
            } else {
                file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " ERROR: SimpleZipExtractor failed for $target.\n", FILE_APPEND);
                header('HTTP/1.0 500 Internal Server Error');
                echo "ERROR: SimpleZipExtractor failed.";
            }
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/deploy_log.txt', date('[Y-m-d H:i:s]') . " ERROR: Exception in SimpleZipExtractor: " . $e->getMessage() . "\n", FILE_APPEND);
            header('HTTP/1.0 500 Internal Server Error');
            echo "ERROR: " . $e->getMessage();
        }
    }
}



