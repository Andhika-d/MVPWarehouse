<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun ini sedang nonaktif. Hubungi administrator.']);
            }

            $request->session()->regenerate();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user_login',
                'target_type' => User::class,
                'target_id' => $user->id,
                'details' => 'User ' . $user->name . ' (' . strtoupper($user->role) . ') berhasil login',
            ]);

            if ($user->must_change_password) {
                return redirect()->route('password.change');
            }

            if ($user->role === 'hr') {
                return redirect()->intended('/hr/dashboard');
            }

            if ($user->role === 'admin') {
                return redirect()->intended('/admin/dashboard');
            }

            return redirect()->intended('/gudang/dashboard');
        }

        return back()->withErrors(['email' => 'Kredensial tidak valid.']);
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user_logout',
                'target_type' => User::class,
                'target_id' => $user->id,
                'details' => 'User ' . $user->name . ' (' . strtoupper($user->role) . ') logged out',
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showChangePassword()
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }

        $user->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'changed_password',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => 'User ' . $user->name . ' mengubah password sendiri',
        ]);

        return redirect($this->homeFor($user->role))->with('success', 'Password berhasil diubah.');
    }

    protected function homeFor(string $role): string
    {
        return match ($role) {
            'hr' => '/hr/dashboard',
            'admin' => '/admin/dashboard',
            default => '/gudang/dashboard',
        };
    }
}
