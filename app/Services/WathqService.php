<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WathqService
{
    public function fetchCrData(string $cr_number): ?array
    {
        try {
            $response = Http::withHeaders([
                'apiKey' => env('API_KEY_WATHQ'),
                'Accept' => 'application/json',
            ])->get(sprintf(
                'https://%s/commercial-registration/fullinfo/%s',
                env('BASE_URL_WATHQ'),
                $cr_number
            ), ['language' => 'en']);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Wathq API Error: '.$response->status(), [
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Wathq API Exception: '.$e->getMessage());

            return null;
        }
    }
}
