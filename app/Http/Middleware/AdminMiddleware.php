<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var App\Models\User */
            $user = Auth::user();
            if($user->hasRole(['superadmin','Demo','demo','helper','admin','purchasing','accounting','produksi','rnd','publikasi','software','marketing','hse','op','administrasi','sekretaris','teknisi','direksi','marketing manager','administration manager','hardware manager','software manager','purchasing level 3','rnd level 3','teknisi level 3', 'produksi level 3','marketing level 3','general_affair'])){
                return $next($request);
            }
            abort(403, "User does not have correct role");
        }
        abort(401);
    }
}
