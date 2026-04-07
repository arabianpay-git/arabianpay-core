<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WathqService
{
    public function fetchCrData(string $cr_number): ?array
    {
        try {
            // [PHASE-5] Replaced env() with config()
            $response = Http::withHeaders([
                'apiKey' => config('services.wathq.api_key'),
                'Accept' => 'application/json',
            ])->get(sprintf(
                '%scommercial-registration/fullinfo/%s',
                rtrim(config('services.wathq.api_base', 'https://api.wathq.sa/'), '/') . '/',
                $cr_number
            ), ['language' => 'en']);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Wathq API Error: ' . $response->status(), [
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Wathq API Exception: ' . $e->getMessage());
            return null;
        }
    }
}
