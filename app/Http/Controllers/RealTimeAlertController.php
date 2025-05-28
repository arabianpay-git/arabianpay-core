<?php

namespace App\Http\Controllers;

use App\Models\RealTimeAlert;
use Illuminate\Http\Request;

class RealTimeAlertController extends Controller
{
    public function index()
    {
        $alerts = RealTimeAlert::with(['user', 'transaction'])->latest()->paginate(10);
        return view('admin.real_time_alerts.index', compact('alerts'));
    }
}
