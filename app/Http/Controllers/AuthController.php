<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LogHelper;

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

    // Metode untuk menangani logout
    public function logout(Request $request)
    {
        $userName = Auth::check() ? Auth::user()->name : 'Guest';
        Auth::guard('web')->logout();
        LogHelper::success('Logout: ' . $userName);
        return redirect('/login');
    }
}
