<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class SettingsController extends Controller
{
    private array $textKeys = [
        'company_name', 'company_address', 'company_phone', 'company_email',
        'company_npwp', 'invoice_prefix', 'bank_name', 'bank_account_no',
        'bank_account_name', 'invoice_notes', 'terms_conditions',
    ];

    private array $numericKeys = ['default_due_days', 'deposit_low_threshold'];

    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name'       => 'nullable|string|max:200',
            'company_address'    => 'nullable|string',
            'company_phone'      => 'nullable|string|max:50',
            'company_email'      => 'nullable|email|max:150',
            'company_npwp'       => 'nullable|string|max:30',
            'invoice_prefix'     => 'required|string|max:10|alpha_num',
            'default_due_days'        => 'required|integer|min:1|max:365',
            'deposit_low_threshold'   => 'required|numeric|min:1',
            'bank_name'          => 'nullable|string|max:100',
            'bank_account_no'    => 'nullable|string|max:50',
            'bank_account_name'  => 'nullable|string|max:150',
            'invoice_notes'      => 'nullable|string',
            'terms_conditions'   => 'nullable|string',
            'logo'               => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
            'favicon'            => 'nullable|file|mimes:png,jpg,jpeg,ico|max:512',
            'navbar_logo'        => 'nullable|file|mimes:png,jpg,jpeg|max:1024',
        ]);

        foreach (array_merge($this->textKeys, $this->numericKeys) as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->input($key));
            }
        }

        if ($request->hasFile('logo')) {
            Setting::set('logo_path', $this->saveFile($request->file('logo'), Setting::get('logo_path')));
        }

        if ($request->hasFile('favicon')) {
            Setting::set('favicon_path', $this->saveFile($request->file('favicon'), Setting::get('favicon_path')));
        }

        if ($request->hasFile('navbar_logo')) {
            Setting::set('navbar_logo_path', $this->saveFile($request->file('navbar_logo'), Setting::get('navbar_logo_path')));
        }

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan berhasil disimpan.');
    }

    private function saveFile(UploadedFile $file, ?string $old): string
    {
        $dir = public_path('uploads/settings');

        if ($old && file_exists(public_path($old))) {
            @unlink(public_path($old));
        }

        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);

        return 'uploads/settings/' . $filename;
    }
}
