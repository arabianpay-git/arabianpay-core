<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ThirdPatryController extends Controller
{
    public function index()
    {
        return view('admin.thirdParty.index');
    }
}
