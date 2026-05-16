<?php
$url = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=TEST-123&choe=UTF-8";
echo "Fetching $url ...\n";
$data = @file_get_contents($url);
if ($data === false) {
    $error = error_get_last();
    echo "FAILED: " . ($error['message'] ?? 'Unknown error') . "\n";
} else {
    echo "SUCCESS: Received " . strlen($data) . " bytes.\n";
}
