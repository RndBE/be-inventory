<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Laravel\Jetstream\Agent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LogActivityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Proceed with the request
        $response = $next($request);

        // Define the URLs or methods to exclude from logging
        $excludedRoutes = [
            'livewire/update',
            // Add more routes if necessary
        ];

        // Check if the current request matches any of the excluded routes
        foreach ($excludedRoutes as $excludedRoute) {
            if (strpos($request->path(), $excludedRoute) !== false) {
                return $response; // Skip logging for this request
            }
        }

        // Log only if it's a CRUD operation
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('delete')) {
            // Remove the _token field from the request data
            $requestData = $request->except('_token');
            $ipAddress = Session::get('ip_address', $request->ip());
            $status = $response->status() == 200 ? 'success' : 'error';
            $agent = new Agent();
            LogActivity::create([
                'user_id' => Auth::id(),
                'method' => $request->method(),
                'ip_address' => $ipAddress,
                'url' => $request->fullUrl(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
            ]);
        }

        return $response;
    }
}




