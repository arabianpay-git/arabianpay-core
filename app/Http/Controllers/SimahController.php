<?php

namespace App\Http\Controllers;

use App\Models\SimahReport;
use App\Services\SimahService;
use Illuminate\Http\Client\RequestException;
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

        $existingReport = SimahReport::where('user_id', $userId)
            ->where('type', 'silverReport')
            ->first();

        if ($existingReport && ! $latest) {
            return response()->json([
                'success' => true,
                'payload' => $existingReport->report_json,
                'source' => 'database',
            ]);
        }

        $data = [
            'idNumber' => $idNumber,
            'nationality' => $request->input('nationality', 196),
            'familyName' => $request->input('familyName', 'ABC'),
            'firstName' => $request->input('firstName', 'ABB'),
            'secondName' => $request->input('secondName', 'BBC'),
            'thirdName' => $request->input('thirdName', 'CCD'),
            'expiryDate' => $request->input('expiryDate', '30/10/2040'),
            'gender' => $request->input('gender', 1),
            'dateOfBirth' => $request->input('dateOfBirth', '30/11/1970'),
            'memberRefNo' => $request->input('memberRefNo'),
        ];

        try {
            $response = $simahService->getSilverReport($data);

            SimahReport::updateOrCreate(
                ['user_id' => $userId, 'type' => 'silverReport'],
                ['report_json' => $response]
            );

            return response()->json([
                'success' => true,
                'payload' => $response,
                'source' => 'simah',
            ]);
        } catch (RequestException $e) {
            // Laravel 12: access response via $e->response property
            $rawBody = $e->response ? (string) $e->response->body() : null;

            Log::error('SIMAH HTTP Client Error (Silver): '.$e->getMessage(), [
                'exception' => $e,
                'simah_response' => $rawBody,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SIMAH API Error',
                'error' => $e->getMessage(),
                'simah_raw' => $rawBody,
            ], 500);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $rawBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : null;

            Log::error('SIMAH Guzzle Error (Silver): '.$e->getMessage(), [
                'exception' => $e,
                'simah_response' => $rawBody,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SIMAH API Error',
                'error' => $e->getMessage(),
                'simah_raw' => $rawBody,
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Fetch SIMAH (Silver) failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SIMAH data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetchConsumerScore(Request $request, SimahService $simahService)
    {
        $idNumber = $request->input('idNumber', '7033970315');
        $latest = $request->input('latest', false);
        $userId = Auth::user()->id;

        $existingReport = SimahReport::where('user_id', $userId)
            ->where('type', 'consumerScore')
            ->first();

        if ($existingReport && ! $latest) {
            return response()->json([
                'success' => true,
                'payload' => $existingReport->report_json,
                'source' => 'database',
            ]);
        }

        $data = [
            'language' => $request->input('language', 'en'),
            'identityInfo' => [
                'idType' => $request->input('idType', 2),
                'idNumber' => $request->input('idNumber', '2583103284'),
                'productId' => $request->input('productId', 23),
            ],
            'applicationDetails' => [
                'amount' => $request->input('amount', 100),
                'productType' => $request->input('productType', 23),
            ],
            'demographicInfo' => [
                'isHijriIDExpiryDate' => $request->input('isHijriIDExpiryDate', true),
                'idExpiryDate' => $request->input('idExpiryDate', $request->input('expiryDate', '30/05/1453')),
                'nationality' => $request->input('nationality', $request->input('nationality', 168)),
                'maritalStatus' => $request->input('maritalStatus', 1),
                'isHijriDateOfBirth' => $request->input('isHijriDateOfBirth', true),
                'dateOfBirth' => $request->input('dateOfBirth', $request->input('dateOfBirth', '09/06/1930')),
                'firstName' => $request->input('firstName', $request->input('firstName', 'Asad')),
                'gender' => $request->input('gender', $request->input('gender', 1)),
                'secondName' => $request->input('secondName', $request->input('secondName', 'Mahmood')),
                'thirdName' => $request->input('thirdName', $request->input('thirdName', 'third')),
                'familyName' => $request->input('familyName', $request->input('familyName', 'family')),
            ],
            'accept' => $request->input('accept', true),
            'referenceNumber' => $request->input('referenceNumber'),
        ];

        try {
            $response = $simahService->consumerScore($data);

            SimahReport::updateOrCreate(
                ['user_id' => $userId, 'type' => 'consumerScore'],
                ['report_json' => $response]
            );

            return response()->json([
                'success' => true,
                'payload' => $response,
                'source' => 'simah',
            ]);
        } catch (RequestException $e) {
            $rawBody = $e->response ? (string) $e->response->body() : null;

            Log::error('SIMAH HTTP Client Error (ConsumerScore): '.$e->getMessage(), [
                'exception' => $e,
                'simah_response' => $rawBody,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SIMAH API Error',
                'error' => $e->getMessage(),
                'simah_raw' => $rawBody,
            ], 500);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $rawBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : null;

            Log::error('SIMAH Guzzle Error (ConsumerScore): '.$e->getMessage(), [
                'exception' => $e,
                'simah_response' => $rawBody,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SIMAH API Error',
                'error' => $e->getMessage(),
                'simah_raw' => $rawBody,
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Fetch SIMAH Consumer Score failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SIMAH consumer score.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
