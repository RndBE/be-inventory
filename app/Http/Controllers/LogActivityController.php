<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use Illuminate\Http\Request;

class LogActivityController extends Controller
{
    public function index()
    {
        $activities = LogActivity::orderBy('created_at', 'desc')->paginate(10);
        return view('pages.log_activities.index', compact('activities'));
    }
}
