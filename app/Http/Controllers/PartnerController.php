<?php

namespace App\Http\Controllers;

use App\Models\CreditClass;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PartnerController extends Controller
{
    private const DOC_FIELDS = [
        'doc_akta_pendirian', 'doc_akta_perubahan', 'doc_surat_kuasa',
        'doc_ktp', 'doc_nib', 'doc_npwp',
    ];

    public function index(Request $request)
    {
        $query = Partner::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_partner', 'like', '%' . $request->search . '%')
                  ->orWhere('nama_pt', 'like', '%' . $request->search . '%')
                  ->orWhere('pic_partner', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('partner_type', $request->type);
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active);
        }

        $partners = $query->orderBy('nama_partner')->paginate(15)->withQueryString();

        // Eager-load for credit status (15 rows max, no N+1 concern)
        $partners->load('invoices.payments', 'creditClass');

        $creditData = [];
        foreach ($partners as $p) {
            $billed      = (float) $p->invoices->sum('grand_total');
            $paid        = (float) $p->invoices->flatMap->payments->sum('amount');
            $outstanding = max(0, $billed - $paid);
            $limit       = (float) $p->limit_credit;
            $util        = ($limit > 0) ? round($outstanding / $limit * 100, 1) : null;

            if ($limit <= 0) {
                $color = 'secondary'; $label = 'No Limit';
            } elseif ($outstanding <= 0) {
                $color = 'success'; $label = 'Lunas';
            } elseif ($util <= 50) {
                $color = 'success'; $label = number_format($util, 0) . '%';
            } elseif ($util <= 80) {
                $color = 'warning'; $label = number_format($util, 0) . '%';
            } elseif ($util <= 100) {
                $color = 'orange'; $label = number_format($util, 0) . '%';
            } else {
                $color = 'danger'; $label = 'Melebihi';
            }

            $creditData[$p->id] = [
                'outstanding' => $outstanding,
                'limit'       => $limit,
                'util'        => $util,
                'color'       => $color,
                'label'       => $label,
            ];
        }

        return view('partners.index', compact('partners', 'creditData'));
    }

    public function create()
    {
        $creditClasses = CreditClass::orderBy('sort_order')->get();
        return view('partners.create', compact('creditClasses'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePartner($request);

        foreach (self::DOC_FIELDS as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('partners/docs', 'public');
            }
        }

        $validated['is_active']  = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        // Auto-assign credit class if not manually selected
        if (empty($validated['credit_class_id'])) {
            $validated['credit_class_id'] = CreditClass::autoAssign((float) $validated['limit_credit'])?->id;
        }

        Partner::create($validated);

        return redirect()->route('partners.index')->with('success', 'Partner berhasil ditambahkan.');
    }

    public function show(Partner $partner)
    {
        $partner->load('invoices.payments', 'creditClass');
        $scorecard      = $this->computeScorecard($partner);
        $recentInvoices = $partner->invoices->sortByDesc('invoice_date')->take(10);
        $depositInfo    = $partner->depositInfo();
        $creditInfo     = $partner->creditInfo();

        return view('partners.show', compact('partner', 'scorecard', 'recentInvoices', 'depositInfo', 'creditInfo'));
    }

    public function creditInfo(Partner $partner)
    {
        $partner->load('creditClass');
        return response()->json($partner->creditInfo());
    }

    // ── Phase 10: Reservation token management ────────────────────────────────

    public function generateReservationToken(Partner $partner)
    {
        $token = \Illuminate\Support\Str::random(48);
        $partner->update([
            'reservation_token'            => $token,
            'reservation_token_expires_at' => now()->addDays(365),
            'known_devices'                => [],
        ]);

        return back()->with('success', 'Token reservasi berhasil digenerate. Partner bisa mengakses link: '
            . route('partner.reserve.form', $token));
    }

    public function resetDevices(Partner $partner)
    {
        $partner->update(['known_devices' => []]);
        return back()->with('success', 'Device list partner berhasil direset. Partner bisa login dari perangkat baru.');
    }

    public function toggleReservationSuspension(Partner $partner)
    {
        if ($partner->reservation_suspended) {
            $partner->update([
                'reservation_suspended'        => false,
                'reservation_suspended_reason' => null,
            ]);
            return back()->with('success', 'Suspensi reservasi partner berhasil dicabut.');
        }

        $partner->update([
            'reservation_suspended'        => true,
            'reservation_suspended_reason' => 'Suspended manually by admin.',
        ]);
        return back()->with('success', 'Partner berhasil disuspend dari reservasi.');
    }

    public function performance(Request $request)
    {
        $query = Partner::with(['invoices.payments']);

        if ($request->filled('type')) {
            $query->where('partner_type', $request->type);
        }

        $partners = $query->orderBy('nama_partner')->get();

        $scorecards = $partners->map(fn($p) => array_merge(['partner' => $p], $this->computeScorecard($p)));

        if ($request->filled('risk')) {
            $scorecards = $scorecards->filter(fn($s) => $s['risk_grade'] === strtoupper($request->risk));
        }

        return view('partners.performance', compact('scorecards'));
    }

    private function computeScorecard(Partner $partner): array
    {
        $invoices    = $partner->invoices;
        $totalBilled = (float) $invoices->sum('grand_total');
        $totalPaid   = (float) $invoices->flatMap->payments->sum('amount');
        $outstanding = max(0, $totalBilled - $totalPaid);

        $paidOnTime      = 0;
        $paidLate        = 0;
        $totalDaysLate   = 0;
        $lastPaymentDate = null;

        foreach ($invoices as $invoice) {
            $lastPay = $invoice->payments->sortByDesc('payment_date')->first();

            if ($lastPay) {
                $payDate = $lastPay->payment_date;
                if (!$lastPaymentDate || $payDate > $lastPaymentDate) {
                    $lastPaymentDate = $payDate;
                }
            }

            if ($invoice->payment_status === 'PAID' && $invoice->due_date && $lastPay) {
                $due  = Carbon::parse($invoice->due_date);
                $paid = Carbon::parse($lastPay->payment_date);

                if ($paid->lte($due)) {
                    $paidOnTime++;
                } else {
                    $paidLate++;
                    $totalDaysLate += $paid->diffInDays($due);
                }
            }
        }

        $overdueCount = $invoices->where('payment_status', 'OVERDUE')->count();
        $unpaidCount  = $invoices->where('payment_status', 'UNPAID')->count();
        $partialCount = $invoices->where('payment_status', 'PARTIAL')->count();

        $resolved    = $paidOnTime + $paidLate;
        $onTimeRate  = $resolved > 0 ? round($paidOnTime / $resolved * 100, 1) : null;
        $avgDaysLate = $paidLate > 0 ? round($totalDaysLate / $paidLate, 1) : 0;

        $limitCredit      = (float) $partner->limit_credit;
        $creditUtilization = $limitCredit > 0 ? round($outstanding / $limitCredit * 100, 1) : null;

        [$riskGrade, $riskColor] = $this->calcRiskGrade($onTimeRate, $overdueCount, $creditUtilization, $invoices->count());

        return [
            'total_invoices'     => $invoices->count(),
            'total_billed'       => $totalBilled,
            'total_paid'         => $totalPaid,
            'outstanding'        => $outstanding,
            'paid_on_time'       => $paidOnTime,
            'paid_late'          => $paidLate,
            'overdue_count'      => $overdueCount,
            'unpaid_count'       => $unpaidCount,
            'partial_count'      => $partialCount,
            'on_time_rate'       => $onTimeRate,
            'avg_days_late'      => $avgDaysLate,
            'credit_utilization' => $creditUtilization,
            'last_payment_date'  => $lastPaymentDate,
            'risk_grade'         => $riskGrade,
            'risk_color'         => $riskColor,
        ];
    }

    private function calcRiskGrade(?float $onTimeRate, int $overdueCount, ?float $creditUtil, int $totalInvoices): array
    {
        if ($totalInvoices === 0) {
            return ['N/A', 'secondary'];
        }

        if ($onTimeRate === null) {
            return $overdueCount > 0 ? ['D', 'danger'] : ['N/A', 'secondary'];
        }

        if ($onTimeRate >= 90 && $overdueCount === 0) {
            $grade = 'A';
            $color = 'success';
        } elseif ($onTimeRate >= 70) {
            $grade = 'B';
            $color = 'primary';
        } elseif ($onTimeRate >= 50) {
            $grade = 'C';
            $color = 'warning';
        } else {
            $grade = 'D';
            $color = 'danger';
        }

        // Downgrade if credit utilization exceeds 100%
        if ($creditUtil !== null && $creditUtil > 100 && in_array($grade, ['A', 'B'])) {
            $grade = 'C';
            $color = 'warning';
        }

        return [$grade, $color];
    }

    public function edit(Partner $partner)
    {
        $creditClasses = CreditClass::orderBy('sort_order')->get();
        return view('partners.edit', compact('partner', 'creditClasses'));
    }

    // Financial fields that only ADMIN/FINANCE_STAFF/FINANCE_MANAGER may edit
    private const FINANCIAL_FIELDS = [
        'bank_name', 'bank_account_no', 'bank_account_name', 'npwp',
        'payment_type', 'payment_due_days', 'limit_credit', 'credit_class_id',
        'doc_npwp',
    ];

    public function update(Request $request, Partner $partner)
    {
        $validated = $this->validatePartner($request, $partner->id);

        // BPM role cannot update financial fields — strip them from validated data
        if (auth()->user()->isBPM()) {
            foreach (self::FINANCIAL_FIELDS as $field) {
                unset($validated[$field]);
            }
        }

        foreach (self::DOC_FIELDS as $field) {
            // BPM cannot upload doc_npwp
            if (auth()->user()->isBPM() && $field === 'doc_npwp') {
                continue;
            }
            if ($request->hasFile($field)) {
                if ($partner->$field) {
                    Storage::disk('public')->delete($partner->$field);
                }
                $validated[$field] = $request->file($field)->store('partners/docs', 'public');
            }
        }

        $validated['is_active']  = $request->boolean('is_active');
        $validated['updated_by'] = auth()->id();

        // Re-auto-assign credit class only when financial fields are being saved
        if (!auth()->user()->isBPM() && empty($validated['credit_class_id'])) {
            $validated['credit_class_id'] = CreditClass::autoAssign((float) $validated['limit_credit'])?->id;
        }

        $partner->update($validated);

        return redirect()->route('partners.index')->with('success', 'Partner berhasil diperbarui.');
    }

    public function destroy(Partner $partner)
    {
        // FINDING-023: Prevent deletion if invoices or deposits exist
        if ($partner->invoices()->exists() || $partner->deposits()->exists()) {
            return redirect()->back()->with('error', 'Partner tidak dapat dihapus karena memiliki riwayat invoice atau deposit.');
        }

        // We are using soft deletes, so we DON'T delete physical files here.
        // File deletion should only happen on forceDelete() if we ever implement it.
        /*
        foreach (self::DOC_FIELDS as $field) {
            if ($partner->$field) {
                Storage::disk('public')->delete($partner->$field);
            }
        }
        */

        $partner->delete();

        return redirect()->route('partners.index')->with('success', 'Partner berhasil dihapus (soft delete).');
    }

    private function validatePartner(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'partner_type'       => 'required|in:HOTEL,TRAVEL,TOURDESK',
            'nama_partner'       => 'required|string|max:200',
            'category'           => 'nullable|string|max:100',
            'channel'            => 'nullable|string|max:100',
            'nama_pt'            => 'nullable|string|max:200',
            'pic_tsbl'           => 'nullable|string|max:150',
            'pic_partner'        => 'nullable|string|max:150',
            'pic_partner_phone'  => 'nullable|string|max:30',
            'pic_partner_email'  => 'nullable|email|max:150',
            'address'            => 'nullable|string',
            'bank_name'          => 'nullable|string|max:100',
            'bank_account_no'    => 'nullable|string|max:50',
            'bank_account_name'  => 'nullable|string|max:150',
            'npwp'               => 'nullable|string|max:30',
            'payment_type'       => 'nullable|string|max:50',
            'payment_due_days'   => 'required|integer|min:0',
            'limit_credit'       => 'required|numeric|min:0',
            'credit_class_id'    => 'nullable|exists:credit_classes,id',
            'contract_start'     => 'nullable|date',
            'contract_end'       => 'nullable|date|after_or_equal:contract_start',
            'notes'              => 'nullable|string',
            'is_active'          => 'boolean',
            'doc_akta_pendirian' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_akta_perubahan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_surat_kuasa'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_ktp'            => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_nib'            => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_npwp'           => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
    }
}
