<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BarcodeRenderer;

$code = 'RES-20260514-001';
$svg = BarcodeRenderer::code39($code, 2, 50, true);

echo "Barcode for $code:\n";
echo $svg . "\n";
