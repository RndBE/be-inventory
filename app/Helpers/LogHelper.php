<?php

namespace App\Helpers;

use Carbon\Carbon;
use Laravel\Jetstream\Agent;
use Illuminate\Support\Facades\Auth;
use App\Models\LogActivity; // Ensure this matches your migration table name

class LogHelper
{
    public static function success($message)
    {
        $status = 'Success';
        $user = Auth::check() ? Auth::user()->name : 'Guest'; // Handle case when user is not authenticated
        $agent = new Agent();
        $logData = [
            'user_id' => Auth::id(), // Save the user ID
            'status' => $status,
            'message' => $message,
            'method' => request()->method(), // You can adjust this based on your requirements
            'ip_address' => request()->ip(), // Get IP address directly from the request
            'url' => request()->fullUrl(), // Current URL
            'platform' => $agent->platform(), // Use Agent for platform
            'browser' => $agent->browser(), // Use Agent for browser
            'created_at' => Carbon::now('Asia/Jakarta'), // Set the created_at timestamp
        ];

        return LogActivity::create($logData);
    }

    public static function error($message)
    {
        $status = 'Error';
        $user = Auth::check() ? Auth::user()->name : 'Guest'; // Handle case when user is not authenticated
        $agent = new Agent();
        $logData = [
            'user_id' => Auth::id(), // Save the user ID
            'status' => $status,
            'message' => $message,
            'method' => 'error', // You can adjust this based on your requirements
            'ip_address' => request()->ip(), // Get IP address directly from the request
            'url' => request()->fullUrl(), // Current URL
            'platform' => $agent->platform(), // Use Agent for platform
            'browser' => $agent->browser(), // Use Agent for browser
            'created_at' => Carbon::now('Asia/Jakarta'), // Set the created_at timestamp
        ];

        return LogActivity::create($logData);
    }
}
