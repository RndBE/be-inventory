<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
            'g-recaptcha-response' => 'required', // wajib dari reCAPTCHA
        ]);

        // Verifikasi token reCAPTCHA ke Google
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $request->input('g-recaptcha-response'),
        ]);

        $recaptcha = $response->json();

        // Jika gagal diverifikasi atau skornya rendah
        if (!($recaptcha['success'] ?? false) || ($recaptcha['score'] ?? 0) < 0.5) {
            return back()->withErrors([
                'captcha' => 'Verifikasi CAPTCHA gagal. Silakan coba lagi.',
            ])->withInput();
        }

        // Jika reCAPTCHA lolos, lanjut autentikasi user
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            // Cek status user aktif
            if ($user->status !== 'Aktif') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
                ]);
            }

            LogHelper::success('Login: ' . $user->name);
            return redirect('/dashboard');
        }

        // Jika gagal login
        return back()->withErrors([
            'email' => 'Email atau password tidak cocok.',
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
