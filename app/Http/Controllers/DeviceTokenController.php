<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceToken;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = $request->user();

        DeviceToken::updateOrCreate(
            ['user_id' => $user->id, 'token' => $request->token],
            []
        );

        return response()->json(['message' => 'Device token saved']);
    }
}
