<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceLog;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature   = 'invoices:mark-overdue';
    protected $description = 'Mark finalized UNPAID invoices past due date as OVERDUE';

    public function handle(): int
    {
        $invoices = Invoice::where('is_finalized', true)
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            $totalPaid = $invoice->totalPaid();
            // PARTIAL invoices stay PARTIAL unless fully unpaid
            if ($totalPaid > 0) {
                continue;
            }

            $invoice->update(['payment_status' => 'OVERDUE']);

            InvoiceLog::create([
                'invoice_id'  => $invoice->id,
                'action'      => 'OVERDUE',
                'description' => "Invoice jatuh tempo pada {$invoice->due_date->format('d/m/Y')} — status diubah ke OVERDUE",
                'old_value'   => 'UNPAID',
                'new_value'   => 'OVERDUE',
                'created_at'  => now(),
            ]);

            $count++;
        }

        $this->info("Marked {$count} invoice(s) as OVERDUE.");
        return Command::SUCCESS;
    }
}
