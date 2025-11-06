<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sanad;
use App\Services\NafithService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class SanadController extends Controller
{
    protected $nafith;

    public function __construct(NafithService $nafith)
    {
        $this->nafith = $nafith;
    }

    public function detail(Request $request, $orderId)
    {
        try {
            $sanad = Sanad::where('order_id', $orderId)->first();

            if (!$sanad) {
                return response()->json(['success' => false, 'message' => 'Sanad not found for this order.'], 404);
            }

            if (empty($sanad->order_id) || empty($sanad->user_id)) {
                return response()->json(['success' => false, 'message' => 'Sanad is missing order_id or user_id.'], 422);
            }

            $raw = $sanad->raw_response ?? [];

            $sanadNumber = Arr::get($raw, 'sanad.0.number') ?? Arr::get($raw, 'number');

            if (empty($sanadNumber)) {
                return response()->json(['success' => false, 'message' => 'SANAD number not found in raw_response.'], 422);
            }

            $result = $this->nafith->getSanadByNumber((string) $sanadNumber);

            if (is_array($result) && isset($result['success']) && $result['success'] === false) {
                return response()->json(['success' => false, 'message' => $result['error'] ?? 'Nafith error', 'payload' => $result], 500);
            }

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('SanadController@detail Exception', ['error' => $e->getMessage(), 'order_id' => $orderId]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function download(Request $request, $orderId)
    {
        try {
            $sanad = Sanad::where('order_id', $orderId)->first();

            if (!$sanad) {
                return response()->json(['success' => false, 'message' => 'Sanad not found for this order.'], 404);
            }

            $raw = $sanad->raw_response ?? [];
            $groupId = Arr::get($raw, 'id');

            if (empty($groupId)) {
                return response()->json(['success' => false, 'message' => 'SANAD group id not found in raw_response.'], 422);
            }

            $result = $this->nafith->downloadSanadGroup((string) $groupId);

            // If service returned a Response instance (binary) — return it directly
            if ($result instanceof Response || $result instanceof \Illuminate\Http\Response) {
                $disposition = 'attachment; filename="sanad-group-' . $groupId . '.pdf"';
                if (! $result->headers->has('Content-Disposition')) {
                    $result->headers->set('Content-Disposition', $disposition);
                }
                return $result;
            }

            // If service returned an error array
            if (is_array($result) && isset($result['success']) && $result['success'] === false) {
                return response()->json(['success' => false, 'message' => $result['error'] ?? 'Download failed', 'payload' => $result], 500);
            }

            // If it's JSON payload (e.g., array) return it
            if (is_array($result)) {
                return response()->json(['success' => true, 'data' => $result]);
            }

            // otherwise assume it's raw binary body
            return response($result, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="sanad-group-' . $groupId . '.pdf"');
        } catch (\Exception $e) {
            Log::error('SanadController@download Exception', ['error' => $e->getMessage(), 'order_id' => $orderId]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
