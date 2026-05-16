<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\BarcodeRenderer;

$svg = BarcodeRenderer::code39('TEST-123', 2, 50, true);
$b64 = base64_encode($svg);
$img = "<img src='data:image/svg+xml;base64,$b64' style='width:300px;' />";

$html = "<html><body><h1>SVG IMG Test</h1><div style='width:300px;border:1px solid red;'>$img</div></body></html>";

$pdf = Pdf::loadHTML($html);
file_put_contents('scratch/test_svg_img_pdf.pdf', $pdf->output());

echo "PDF generated at scratch/test_svg_img_pdf.pdf\n";
