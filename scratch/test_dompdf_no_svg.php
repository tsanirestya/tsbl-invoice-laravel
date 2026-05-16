<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Barryvdh\DomPDF\Facade\Pdf;

$html = "<html><body><h1>SVG Test</h1><div style='width:300px;border:1px solid red;'>[NO SVG]</div></body></html>";

$pdf = Pdf::loadHTML($html);
file_put_contents('scratch/test_no_svg_pdf.pdf', $pdf->output());
