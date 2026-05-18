<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    private array $textKeys = [
        'company_name', 'company_address', 'company_phone', 'company_email',
        'company_npwp', 'invoice_prefix', 'bank_name', 'bank_account_no',
        'bank_account_name', 'invoice_notes', 'terms_conditions',
    ];

    private array $numericKeys = [
        'default_due_days', 'deposit_low_threshold',
        'credit_warning_threshold',
        'credit_aging_bucket_1', 'credit_aging_bucket_2',
        'credit_aging_bucket_3', 'credit_aging_bucket_4',
        'admission_visit_date_tolerance_days',
    ];

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
            'credit_warning_threshold'  => 'required|integer|min:1|max:100',
            'credit_aging_bucket_1'     => 'required|integer|min:1|max:999',
            'credit_aging_bucket_2'     => 'required|integer|min:1|max:999',
            'credit_aging_bucket_3'     => 'required|integer|min:1|max:999',
            'credit_aging_bucket_4'     => 'required|integer|min:1|max:999',
            'admission_visit_date_tolerance_days' => 'nullable|integer|min:0|max:7',
            'logo'               => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
            'favicon'            => 'nullable|file|mimes:png,jpg,jpeg,ico|max:512',
            'navbar_logo'        => 'nullable|file|mimes:png,jpg,jpeg|max:1024',
        ]);

        $b1 = (int) $request->credit_aging_bucket_1;
        $b2 = (int) $request->credit_aging_bucket_2;
        $b3 = (int) $request->credit_aging_bucket_3;
        $b4 = (int) $request->credit_aging_bucket_4;

        if (!($b1 < $b2 && $b2 < $b3 && $b3 < $b4)) {
            return back()->withInput()->withErrors([
                'credit_aging_bucket_2' => 'Urutan bucket harus: Bucket 1 < Bucket 2 < Bucket 3 < Bucket 4.',
            ]);
        }

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

    public function toggleDevMode()
    {
        $current = Setting::get('dev_mode_enabled', '0');
        Setting::set('dev_mode_enabled', $current === '1' ? '0' : '1');

        $state = $current === '1' ? 'dinonaktifkan' : 'diaktifkan';
        return redirect()->route('settings.index')
            ->with($current === '1' ? 'success' : 'warning', "Dev Mode {$state}.");
    }

    private function saveFile(UploadedFile $file, ?string $old): string
    {
        // Delete old file from storage disk (strip leading 'storage/' prefix)
        if ($old) {
            $oldKey = ltrim(preg_replace('#^storage/#', '', $old), '/');
            if ($oldKey && Storage::disk('public')->exists($oldKey)) {
                Storage::disk('public')->delete($oldKey);
            }
        }

        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        Storage::disk('public')->putFileAs('settings', $file, $filename);

        // Return URL-relative path so asset('storage/settings/...') works in views
        return 'storage/settings/' . $filename;
    }
}
