<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            // Cek status user
            if ($user->status !== 'Aktif') {
                Auth::logout(); // langsung logout jika status bukan Aktif
                return back()->withErrors([
                    'email' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
                ]);
            }

            LogHelper::success('Login: ' . $user->name);
            return redirect('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function autoLogin($token)
    {
        $user = User::where('auto_login_token', $token)->first();

        if ($user) {
            if ($user->status !== 'Aktif') {
                abort(403, 'Akun Anda tidak aktif.');
            }

            Auth::login($user);
            return redirect('/dashboard');
        }

        abort(403);
    }


    // Metode untuk menangani logout
    public function logout(Request $request)
    {
        $userName = Auth::check() ? Auth::user()->name : 'Guest';
        Auth::guard('web')->logout();
        LogHelper::success('Logout: ' . $userName);
        return redirect('/login');
    }
}
