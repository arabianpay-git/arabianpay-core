<?php

namespace App\Http\Controllers;

use App\Models\SimahReport;
use App\Services\SimahService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SimahController extends Controller
{
    public function fetchCustomerSimah(Request $request, SimahService $simahService)
    {
        $idNumber = $request->input('idNumber', '7033970315');
        $latest = $request->input('latest', false);

        $userId = Auth::user()->id;

        // check if report exists in DB and latest is not requested
        $existingReport = SimahReport::where('user_id', $userId)->where('type', 'silverReport')->first();

        if ($existingReport && !$latest) {
            return response()->json([
                'success' => true,
                'payload' => $existingReport->report_json,
                'source'  => 'database',
            ]);
        }

        // Build data for SIMAH request
        $data = [
            'idNumber'    => $idNumber,
            'nationality' => $request->input('nationality', 196),
            'familyName'  => $request->input('familyName', 'ABC'),
            'firstName'   => $request->input('firstName', 'ABB'),
            'secondName'  => $request->input('secondName', 'BBC'),
            'thirdName'   => $request->input('thirdName', 'CCD'),
            'expiryDate'  => $request->input('expiryDate', '30/10/2040'),
            'gender'      => $request->input('gender', 1),
            'dateOfBirth' => $request->input('dateOfBirth', '30/11/1970'),
            'memberRefNo' => $request->input('memberRefNo'),
        ];

        try {
            $response = $simahService->getSilverReport($data);

            SimahReport::updateOrCreate(
                [
                    'user_id' => $userId,
                    'type'    => 'silverReport',
                ],
                ['report_json' => $response]
            );

            return response()->json([
                'success' => true,
                'payload' => $response,
                'source'  => 'simah',
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Guzzle exception (has getResponse())
            $rawBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : null;

            Log::error('SIMAH Guzzle Error: ' . $e->getMessage(), [
                'exception' => $e,
                'simah_response' => $rawBody,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SIMAH API Error',
                'error'   => $e->getMessage(),
                'simah_raw' => $rawBody,
            ], 500);
        } catch (\Throwable $e) {
            // Generic fallback
            Log::error('Fetch SIMAH failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SIMAH data.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
