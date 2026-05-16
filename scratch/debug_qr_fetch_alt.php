<?php
$url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=TEST-123";
echo "Fetching $url ...\n";
$data = @file_get_contents($url);
if ($data === false) {
    $error = error_get_last();
    echo "FAILED: " . ($error['message'] ?? 'Unknown error') . "\n";
} else {
    echo "SUCCESS: Received " . strlen($data) . " bytes.\n";
}
