<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// F-017: Mark overdue invoices daily — removed from DashboardController side-effect
Schedule::command('invoices:mark-overdue')->dailyAt('00:01');
