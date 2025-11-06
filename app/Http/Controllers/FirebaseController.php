<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Throwable;

class FirebaseController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function sendNotification(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        try {
            $response = $this->firebase->sendNotification(
                $request->device_token,
                $request->title,
                $request->body
            );

            return response()->json(['message' => $response]);
        } catch (Throwable $e) {
            return response()->json(['error' => 'Failed to send notification', 'details' => $e->getMessage()], 500);
        }
    }
}
