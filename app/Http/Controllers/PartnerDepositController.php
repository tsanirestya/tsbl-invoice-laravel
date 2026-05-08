<?php

namespace App\Http\Controllers;

use App\Models\InvoiceLog;
use App\Models\Partner;
use App\Models\PartnerDeposit;
use App\Models\Setting;
use Illuminate\Http\Request;

class PartnerDepositController extends Controller
{
    public function index(Partner $partner)
    {
        $deposits  = $partner->deposits()->with('creator', 'invoice')->orderByDesc('created_at')->paginate(30);
        $info      = $partner->depositInfo();

        return view('partners.deposit.index', compact('partner', 'deposits', 'info'));
    }

    public function create(Partner $partner)
    {
        return view('partners.deposit.topup', compact('partner'));
    }

    public function store(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'amount'       => 'required|numeric|min:1',
            'reference_no' => 'nullable|string|max:100',
            'notes'        => 'nullable|string',
        ]);

        PartnerDeposit::create([
            'partner_id'   => $partner->id,
            'type'         => 'TOPUP',
            'amount'       => $validated['amount'],
            'reference_no' => $validated['reference_no'] ?? null,
            'notes'        => $validated['notes'] ?? null,
            'created_by'   => auth()->id(),
            'created_at'   => now(),
        ]);

        return redirect()->route('deposits.index', $partner)
            ->with('success', 'Top-up deposit Rp ' . number_format($validated['amount'], 0, ',', '.') . ' berhasil dicatat.');
    }

    public function adjustment(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'notes'  => 'required|string',
        ]);

        $amount = (float) $validated['amount'];

        if (abs($amount) > 500_000_000) {
            return back()->withErrors(['amount' => 'Nilai adjustment tidak boleh melebihi Rp 500.000.000.']);
        }

        if ($partner->depositBalance() + $amount < 0) {
            return back()->withErrors(['amount' => 'Adjustment akan membuat saldo deposit menjadi negatif.']);
        }

        PartnerDeposit::create([
            'partner_id' => $partner->id,
            'type'       => 'ADJUSTMENT',
            'amount'     => $validated['amount'],
            'notes'      => $validated['notes'],
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('deposits.index', $partner)
            ->with('success', 'Adjustment deposit berhasil dicatat.');
    }

    public function balance(Partner $partner)
    {
        $info = $partner->depositInfo();

        return response()->json([
            'partner_id'        => $partner->id,
            'partner_name'      => $partner->nama_partner,
            'balance'           => $info['balance'],
            'balance_formatted' => $info['balance_formatted'],
            'threshold'         => $info['threshold'],
            'is_low'            => $info['is_low'],
            'is_empty'          => $info['is_empty'],
        ]);
    }
}
