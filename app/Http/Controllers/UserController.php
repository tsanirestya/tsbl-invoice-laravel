<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('user_status', $request->status);
        }

        $users = $query->orderBy('full_name')->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name'      => 'required|string|max:150',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'nullable|string|max:30',
            'password'       => 'required|string|min:6|confirmed',
            'user_status'    => 'required|in:ADMIN,FINANCE,SALES,VIEWER',
            'position_name'  => 'nullable|string|max:100',
            'is_active'      => 'boolean',
            'signature_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('signature_image')) {
            $validated['signature_image'] = $request->file('signature_image')
                ->store('signatures', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['password']  = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'full_name'      => 'required|string|max:150',
            'email'          => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone'          => 'nullable|string|max:30',
            'password'       => 'nullable|string|min:6|confirmed',
            'user_status'    => 'required|in:ADMIN,FINANCE,SALES,VIEWER',
            'position_name'  => 'nullable|string|max:100',
            'is_active'      => 'boolean',
            'signature_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('signature_image')) {
            if ($user->signature_image) {
                Storage::disk('public')->delete($user->signature_image);
            }
            $validated['signature_image'] = $request->file('signature_image')
                ->store('signatures', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        if ($user->signature_image) {
            Storage::disk('public')->delete($user->signature_image);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
