<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    // ── Guest: request password reset ────────────────────────────────────────

    public function showRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function submitRequest(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Email tidak terdaftar.',
        ]);

        $user = User::where('email', $request->email)->first();

        // Prevent spam: ignore if already requested in last 30 minutes
        if ($user->reset_requested_at && $user->reset_requested_at->gt(now()->subMinutes(30))) {
            return back()->with('info', 'Permintaan sudah dikirim. Hubungi admin untuk proses lanjutan.');
        }

        $user->update(['reset_requested_at' => now()]);

        return back()->with('success', 'Permintaan berhasil dikirim. Hubungi admin untuk mendapatkan password sementara.');
    }

    // ── Admin: manage reset requests ─────────────────────────────────────────

    public function listRequests()
    {
        $requests = User::whereNotNull('reset_requested_at')
            ->orderByDesc('reset_requested_at')
            ->get();

        return view('admin.password-requests.index', compact('requests'));
    }

    public function resolveRequest(Request $request, User $user)
    {
        $request->validate([
            'temp_password' => 'required|string|min:8',
        ]);

        $user->update([
            'password'                 => $request->temp_password,
            'password_change_required' => true,
            'reset_requested_at'       => null,
        ]);

        return back()->with('success', "Password sementara untuk {$user->full_name} berhasil diset. User harus ganti password saat login.");
    }

    // ── Authenticated: force change password ─────────────────────────────────

    public function showChangeForm()
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = Auth::user();

        $user->update([
            'password'                 => $request->password,
            'password_change_required' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Password berhasil diubah. Selamat datang!');
    }
}
