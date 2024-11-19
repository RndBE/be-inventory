<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Session;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Laravel\Jetstream\Agent;
use Illuminate\Support\Facades\Auth;

class LogActivityController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-log-activity', ['only' => ['index']]);
    }

    public function index()
    {

        return view('pages.log_activities.index');
    }
}
