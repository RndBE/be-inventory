<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class InventoryTokenController extends Controller
{
    public function generate(User $user)
    {
        // generate token random unik
        $token = Str::random(60);
        $user->auto_login_token = $token;
        $user->save();

        return redirect()->back()->with('success', "Token untuk {$user->name} berhasil digenerate: {$token}");
    }
}
