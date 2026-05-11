<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Partner;
use App\Models\Product;

echo "--- PARTNERS ---\n";
Partner::distinct()->get(['category', 'partner_type'])->each(function($p) {
    echo "Category: {$p->category} | Type: {$p->partner_type}\n";
});

echo "\n--- PRODUCTS ---\n";
Product::distinct()->get(['category', 'partner_type'])->each(function($p) {
    echo "Category: {$p->category} | Partner Type: {$p->partner_type}\n";
});
