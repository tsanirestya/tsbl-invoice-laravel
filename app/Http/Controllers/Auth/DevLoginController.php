<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DevLoginController extends Controller
{
    // Dashboard access per role — mirrors the route middleware matrix
    private const ROLE_REDIRECT = [
        'ADMIN'             => 'dashboard',
        'IT'                => 'users.index',       // IT excluded from dashboard
        'BUSDEV_HO'         => 'dashboard',
        'FINANCE_STAFF'     => 'dashboard',
        'FINANCE_MANAGER'   => 'dashboard',
        'BPM'               => 'dashboard',
        'RESERVATION_STAFF' => 'dashboard',
        'ADMISSION'         => 'admission.dashboard',
    ];

    public function login(string $role)
    {
        if (Setting::get('dev_mode_enabled', '0') !== '1') {
            abort(403, 'Dev mode tidak aktif.');
        }

        $user = User::where('user_status', $role)
            ->where('email', 'like', '%@tsbl.dev')
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['email' => "Dev user untuk role [{$role}] tidak ditemukan."]);
        }

        Auth::login($user);
        request()->session()->regenerate();

        $routeName = self::ROLE_REDIRECT[$role] ?? 'dashboard';
        return redirect()->route($routeName);
    }
}
