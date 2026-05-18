<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkNoShowReservations extends Command
{
    protected $signature   = 'reservations:mark-no-show';
    protected $description = 'Mark CONFIRMED reservations past their visit_date as NO_SHOW';

    public function handle(): int
    {
        $count = Reservation::where('status', 'CONFIRMED')
            ->whereDate('visit_date', '<', Carbon::today())
            ->update(['status' => 'NO_SHOW']);

        $this->info("Marked {$count} reservation(s) as NO_SHOW.");

        return Command::SUCCESS;
    }
}
