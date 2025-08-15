<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QualityPageController extends Controller
{
    public function index()
    {
        return view('pages.quality-page.dashboard');
    }
}
