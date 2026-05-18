<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// F-026: mark finalized UNPAID/PARTIAL invoices past due date as OVERDUE every midnight
Schedule::command('invoices:mark-overdue')->dailyAt('00:01');

// Phase 12: auto-mark CONFIRMED reservations past visit_date as NO_SHOW every midnight
Schedule::command('reservations:mark-no-show')->dailyAt('00:05');
