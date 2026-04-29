<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            if ($user->hasRole('Super Admin')) {
                return redirect()->intended(route('super-admin.dashboard'));
            } elseif ($user->hasRole('School Admin')) {
                return redirect()->intended(route('admin.dashboard'));
            } elseif ($user->hasRole('FKKG Admin')) {
                return redirect()->intended(route('fkkg.dashboard'));
            } elseif ($user->hasRole('Student')) {
                return redirect()->intended(route('siswa.dashboard'));
            }
            
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Kredensial yang Anda masukkan tidak cocok dengan catatan kami.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
