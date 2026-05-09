<?php

use App\Jobs\MarkOverdueInvoicesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// F-017 / Phase-C: Mark overdue invoices daily via artisan command (legacy)
Schedule::command('invoices:mark-overdue')->dailyAt('00:01');

// Phase C — C1: MarkOverdueInvoicesJob (queue-based, chunk-safe, audit-logged)
// Dispatches to default queue 1 minute after midnight
Schedule::job(new MarkOverdueInvoicesJob())->dailyAt('00:02');

