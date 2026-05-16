<?php
$url = "https://invoice.transentertainment.id/force_extract.php";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Set a long timeout for extraction
curl_setopt($ch, CURLOPT_TIMEOUT, 300); 
$response = curl_exec($ch);
curl_close($ch);

echo $response;
