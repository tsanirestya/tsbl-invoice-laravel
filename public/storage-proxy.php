<?php
/**
 * Storage file proxy for production shared hosting.
 *
 * On production, the webroot (/invoice.transentertainment.id/) is separate
 * from public/ inside the Laravel app, so storage:link symlinks don't work.
 * This script reads files from storage/app/public/ and streams them.
 */

$rel = $_GET['path'] ?? '';

// Reject empty or traversal attempts
if ($rel === '' || str_contains($rel, '..') || str_contains($rel, "\0")) {
    http_response_code(400);
    exit;
}

// Resolve the storage base — works for both local and production layouts
$production = realpath(__DIR__ . '/../tsbl-invoice-laravel/storage/app/public'); // prod
$local      = realpath(__DIR__ . '/../storage/app/public');                      // local

$base = ($production !== false) ? $production : $local;

if (!$base) {
    http_response_code(500);
    exit;
}

$file = realpath($base . '/' . $rel);

// Ensure resolved path is inside the storage base (prevent traversal)
if ($file === false || strncmp($file, $base, strlen($base)) !== 0 || !is_file($file)) {
    http_response_code(404);
    exit;
}

$mime = mime_content_type($file) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Cache-Control: public, max-age=31536000');
readfile($file);
