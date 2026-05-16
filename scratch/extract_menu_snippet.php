<?php
$url = "https://invoice.transentertainment.id/file_check.php";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

// Search for the Menu Snippet section and print more lines
if (preg_match('/<h4>Menu Snippet \(Lines 300-320\):<\/h4><pre>(.*)<\/pre>/s', $response, $matches)) {
    echo $matches[1];
} else {
    echo "Pattern not found";
}
