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
            $userName = Auth::user()->name;
            LogHelper::success('Login: ' . $userName);
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
