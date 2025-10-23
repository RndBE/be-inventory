<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
            'g-recaptcha-response' => 'required', // reCAPTCHA wajib
        ]);

        // Verifikasi reCAPTCHA ke Google
        $recaptcha = $request->input('g-recaptcha-response');
        $secretKey = env('RECAPTCHA_SECRET_KEY'); // simpan di .env
        $verifyResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $recaptcha,
            'remoteip' => $request->ip(),
        ]);

        $captchaSuccess = $verifyResponse->json()['success'] ?? false;
        if (!$captchaSuccess) {
            return back()->withErrors(['email' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.'])->withInput();
        }

        // === LOGIN PROSES MULAI ===
        $email = strtolower($request->input('email'));
        $key = 'login-attempt:' . $email;
        $maxAttempts = 3;
        $decaySeconds = 60; // 1 menit

        $user = User::where('email', $email)->first();

        // Cek apakah akun terkunci
        if ($user && $user->locked_until) {
            try {
                $lockedUntil = Carbon::parse($user->locked_until)->timezone('Asia/Jakarta');

                if ($lockedUntil->greaterThan(now())) {
                    $remainingSeconds = now()->diffInSeconds($lockedUntil);
                    $minutes = floor($remainingSeconds / 60);
                    $seconds = $remainingSeconds % 60;
                    $remainingFormatted = sprintf('%02d:%02d', $minutes, $seconds);

                    return back()->withErrors([
                        'email' => "Akun Anda terkunci sementara. Silakan coba lagi dalam $remainingFormatted.",
                    ])->withInput();
                } else {
                    $user->locked_until = null;
                    $user->save();
                }
            } catch (\Exception $e) {
                $user->locked_until = null;
                $user->save();
            }
        }

        // Jika terlalu banyak percobaan gagal
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            if ($user) {
                $user->locked_until = now()->addSeconds($seconds);
                $user->save();
            }

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login gagal. Coba lagi dalam " . ceil($seconds / 60) . " menit.",
            ])->withInput();
        }

        // Jika login berhasil
        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $user = Auth::user();

            if ($user->status !== 'Aktif') {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun Anda tidak aktif. Silakan hubungi administrator.']);
            }

            RateLimiter::clear($key);
            $user->locked_until = null;
            $user->save();

            LogHelper::success('Login: ' . $user->name);
            return redirect()->intended('/dashboard');
        }

        // Jika gagal login
        RateLimiter::hit($key, $decaySeconds);
        $attemptsLeft = $maxAttempts - RateLimiter::attempts($key);
        $message = 'Email atau password salah.';
        if ($attemptsLeft > 0) {
            $message .= " Sisa percobaan: {$attemptsLeft}.";
        } else {
            $seconds = RateLimiter::availableIn($key);
            if ($user) {
                $user->locked_until = now()->addSeconds($seconds);
                $user->save();
            }
            $message = "Terlalu banyak percobaan login gagal. Akun terkunci selama " . ceil($seconds / 60) . " menit.";
        }

        return back()->withErrors(['email' => $message])->withInput();
    }
    //     public function login(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email',
    //         'password' => 'required|min:6',
    //     ]);

    //     $email = strtolower($request->input('email'));
    //     $key = 'login-attempt:' . $email;
    //     $maxAttempts = 3;        // maksimal 3 kali percobaan
    //     $decaySeconds = 60;      // 1 menit (60 detik)

    //     $user = User::where('email', $email)->first();

    //     // Cek apakah user terkunci
    //     if ($user && $user->locked_until) {
    //         try {
    //             // $lockedUntil = Carbon::parse($user->locked_until);
    //             $lockedUntil = Carbon::parse($user->locked_until)->timezone('Asia/Jakarta');

    //             // Hanya jalankan jika waktu kunci masih di masa depan
    //             if ($lockedUntil->greaterThan(now())) {
    //                 $remainingSeconds = now()->diffInSeconds($lockedUntil);
    //                 $minutes = floor($remainingSeconds / 60);
    //                 $seconds = $remainingSeconds % 60;
    //                 $remainingFormatted = sprintf('%02d:%02d', $minutes, $seconds);

    //                 return back()->withErrors([
    //                     'email' => "Akun Anda terkunci sementara. Silakan coba lagi dalam $remainingFormatted.",
    //                 ])->withInput();
    //             } else {
    //                 // Sudah lewat → hapus kunci
    //                 $user->locked_until = null;
    //                 $user->save();
    //             }
    //         } catch (\Exception $e) {
    //             $user->locked_until = null;
    //             $user->save();
    //         }
    //     }


    //     // Jika sudah terlalu banyak percobaan gagal
    //     if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
    //         $seconds = RateLimiter::availableIn($key);
    //         if ($user) {
    //             $user->locked_until = Carbon::now('Asia/Jakarta')->addSeconds($seconds);
    //             $user->save();
    //         }

    //         return back()->withErrors([
    //             'email' => "Terlalu banyak percobaan login gagal. Coba lagi dalam " . ceil($seconds / 60) . " menit.",
    //         ])->withInput();
    //     }

    //     // Jika login sukses
    //     if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
    //         $user = Auth::user();

    //         if ($user->status !== 'Aktif') {
    //             Auth::logout();
    //             return back()->withErrors([
    //                 'email' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
    //             ]);
    //         }

    //         // Reset limiter & unlock akun
    //         RateLimiter::clear($key);
    //         $user->locked_until = null;
    //         $user->save();

    //         LogHelper::success('Login: ' . $user->name);
    //         return redirect()->intended('/dashboard');
    //     }

    //     // Jika gagal login
    //     RateLimiter::hit($key, $decaySeconds);
    //     $attemptsLeft = $maxAttempts - RateLimiter::attempts($key);
    //     $message = 'Email atau password salah.';
    //     if ($attemptsLeft > 0) {
    //         $message .= " Sisa percobaan: {$attemptsLeft}.";
    //     } else {
    //         $seconds = RateLimiter::availableIn($key);
    //         if ($user) {
    //             $user->locked_until = Carbon::now('Asia/Jakarta')->addSeconds($seconds);
    //             $user->save();
    //         }
    //         $message = "Terlalu banyak percobaan login gagal. Akun terkunci selama " . ceil($seconds / 60) . " menit.";
    //     }

    //     return back()->withErrors(['email' => $message])->withInput();
    // }


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
