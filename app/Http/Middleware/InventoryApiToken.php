<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InventoryApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-API-KEY');

        if ($token !== env('INVENTORY_API_KEY')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
