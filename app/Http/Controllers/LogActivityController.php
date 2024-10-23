<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Session;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Laravel\Jetstream\Agent;

class LogActivityController extends Controller
{
    public function index()
    {
        $activities = LogActivity::orderBy('created_at', 'desc')->paginate(10);

        $activeSessions = Session::where('last_activity', '>=', now()->subMinutes(5)->timestamp)->get(); // Adjust time as needed

        // Initialize an array to store active sessions with agent details
        $activeSessionsWithAgent = [];

        foreach ($activeSessions as $session) {
            $agent = new Agent();
            $agent->setUserAgent($session->user_agent);
            $user = User::find($session->user_id);

            $activeSessionsWithAgent[] = [
                'user' => $user ? $user->name : 'Unknown User',
                'ip_address' => $session->ip_address,
                'last_active' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
            ];
        }

        return view('pages.log_activities.index', compact('activities', 'activeSessionsWithAgent'));
    }
}
