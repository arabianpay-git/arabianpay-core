<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiTesterController extends Controller
{
    // Show the form
    public function index()
    {
        return view('api-tester.index');
    }

    // Handle API request sending
    public function send(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'method' => 'required|in:GET,POST,PUT,DELETE,PATCH',
            'body' => 'nullable|json',
            'headers' => 'nullable|json',
        ]);

        $method = strtoupper($request->method);
        $url = $request->endpoint;
        $body = $request->body ? json_decode($request->body, true) : [];
        $headers = $request->headers ? json_decode($request->headers, true) : [];

        try {
            $client = Http::withHeaders($headers);

            $response = match ($method) {
                'GET' => $client->get($url, $body),
                'POST' => $client->post($url, $body),
                'PUT' => $client->put($url, $body),
                'PATCH' => $client->patch($url, $body),
                'DELETE' => $client->delete($url, $body),
                default => throw new \Exception("Invalid method"),
            };

            return back()->with([
                'response' => $response->body(),
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
