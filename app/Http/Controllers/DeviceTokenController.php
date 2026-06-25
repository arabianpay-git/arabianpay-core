<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = $request->user();

        // Check if token already exists
        $existingToken = DeviceToken::where('user_id', $user->id)
            ->where('token', $request->token)
            ->first();

        if (! $existingToken) {
            // Get token count
            $tokenCount = DeviceToken::where('user_id', $user->id)->count();

            // If more than 4 already exist, delete the oldest
            if ($tokenCount >= 5) {
                DeviceToken::where('user_id', $user->id)
                    ->oldest()
                    ->first()
                    ?->delete();
            }

            // Store new token
            DeviceToken::create([
                'user_id' => $user->id,
                'token' => $request->token,
            ]);
        }

        return response()->json(['message' => 'Token stored.']);
    }
}
