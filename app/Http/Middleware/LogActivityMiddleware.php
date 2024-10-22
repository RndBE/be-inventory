<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\LogActivity;
use Illuminate\Support\Facades\Auth;

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
            $status = $response->status() == 200 ? 'success' : 'error';
            LogActivity::create([
                'description' => 'User ' . Auth::user()->name . ' performed ' . $request->method() . ' on ' . $request->path(),
                'user_id' => Auth::id(), // Get the authenticated user ID
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'url' => $request->fullUrl(),
                'data' => json_encode($requestData), // Log the filtered request data
                'status' => $status
            ]);
        }

        return $response;
    }
}




