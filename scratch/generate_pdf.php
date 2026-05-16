<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Services\BookingPassService;

$reservation = Reservation::find(11);
if (!$reservation) {
    echo "Reservation not found\n";
    exit;
}

$service = new BookingPassService();
$path = $service->generate($reservation);

echo "PDF generated at: storage/app/public/$path\n";
