<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        return view('partners.index', compact('partners'));
    }

    public function create()
    {
        return view('partners.create');
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

        Partner::create($validated);

        return redirect()->route('partners.index')->with('success', 'Partner berhasil ditambahkan.');
    }

    public function show(Partner $partner)
    {
        return view('partners.show', compact('partner'));
    }

    public function edit(Partner $partner)
    {
        return view('partners.edit', compact('partner'));
    }

    public function update(Request $request, Partner $partner)
    {
        $validated = $this->validatePartner($request, $partner->id);

        foreach (self::DOC_FIELDS as $field) {
            if ($request->hasFile($field)) {
                if ($partner->$field) {
                    Storage::disk('public')->delete($partner->$field);
                }
                $validated[$field] = $request->file($field)->store('partners/docs', 'public');
            }
        }

        $validated['is_active']  = $request->boolean('is_active');
        $validated['updated_by'] = auth()->id();

        $partner->update($validated);

        return redirect()->route('partners.index')->with('success', 'Partner berhasil diperbarui.');
    }

    public function destroy(Partner $partner)
    {
        foreach (self::DOC_FIELDS as $field) {
            if ($partner->$field) {
                Storage::disk('public')->delete($partner->$field);
            }
        }

        $partner->delete();

        return redirect()->route('partners.index')->with('success', 'Partner berhasil dihapus.');
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
