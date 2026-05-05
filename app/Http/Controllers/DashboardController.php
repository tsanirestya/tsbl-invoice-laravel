<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Partner;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        Invoice::where('payment_status', '!=', 'PAID')
            ->where('due_date', '<', $today)
            ->update(['payment_status' => 'OVERDUE']);

        $stats = [
            'total'       => Invoice::count(),
            'unpaid'      => Invoice::where('payment_status', 'UNPAID')->count(),
            'partial'     => Invoice::where('payment_status', 'PARTIAL')->count(),
            'paid'        => Invoice::where('payment_status', 'PAID')->count(),
            'overdue'     => Invoice::where('payment_status', 'OVERDUE')->count(),
            'revenue'     => Invoice::where('payment_status', 'PAID')->sum('grand_total'),
            'outstanding' => Invoice::whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])->sum('grand_total'),
        ];

        $recentInvoices = Invoice::with('partner')
            ->latest()
            ->limit(10)
            ->get();

        $totalPartners = Partner::where('is_active', true)->count();

        return view('dashboard.index', compact('stats', 'recentInvoices', 'totalPartners'));
    }
}
