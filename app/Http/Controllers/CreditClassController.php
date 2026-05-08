<?php

namespace App\Http\Controllers;

use App\Models\CreditClass;
use Illuminate\Http\Request;

class CreditClassController extends Controller
{
    public function index()
    {
        $creditClasses = CreditClass::withCount('partners')->orderBy('sort_order')->get();
        return view('credit-classes.index', compact('creditClasses'));
    }

    public function create()
    {
        return view('credit-classes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:credit_classes,name',
            'color'       => 'required|in:primary,secondary,success,warning,danger,info,dark',
            'min_limit'   => 'required|numeric|min:0',
            'max_limit'   => 'nullable|numeric|gt:min_limit',
            'description' => 'nullable|string|max:255',
            'sort_order'  => 'required|integer|min:0',
        ]);

        CreditClass::create($validated);

        return redirect()->route('credit-classes.index')->with('success', 'Credit class berhasil ditambahkan.');
    }

    public function edit(CreditClass $creditClass)
    {
        return view('credit-classes.edit', compact('creditClass'));
    }

    public function update(Request $request, CreditClass $creditClass)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:credit_classes,name,' . $creditClass->id,
            'color'       => 'required|in:primary,secondary,success,warning,danger,info,dark',
            'min_limit'   => 'required|numeric|min:0',
            'max_limit'   => 'nullable|numeric|gt:min_limit',
            'description' => 'nullable|string|max:255',
            'sort_order'  => 'required|integer|min:0',
        ]);

        $creditClass->update($validated);

        return redirect()->route('credit-classes.index')->with('success', 'Credit class berhasil diperbarui.');
    }

    public function destroy(CreditClass $creditClass)
    {
        $partnerCount = $creditClass->partners()->count();

        if ($partnerCount > 0) {
            return back()->with('error', "Tidak dapat menghapus \"{$creditClass->name}\" — {$partnerCount} partner masih menggunakan class ini.");
        }

        $creditClass->delete();

        return redirect()->route('credit-classes.index')->with('success', 'Credit class berhasil dihapus.');
    }
}
