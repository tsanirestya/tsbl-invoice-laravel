<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// F-026: mark finalized UNPAID/PARTIAL invoices past due date as OVERDUE every midnight
Schedule::command('invoices:mark-overdue')->dailyAt('00:01');
