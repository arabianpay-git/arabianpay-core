<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SimahReport;
use App\Services\SimahService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SimahController extends Controller
{
    public function fetchCustomerSimah(Request $request, SimahService $simahService)
    {
        // 1. Validate required input (user_id)
        $request->validate(['user_id' => 'required|integer']);
        $userId = $request->user_id;

        // 2. Retrieve Customer and User data
        // Use find() for simplicity, assuming user_id maps directly to the primary key
        // If user_id is a foreign key on Customer model, the original query is correct:
        $customer = Customer::with('user')->where('user_id', $userId)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }

        // 3. Check for CR number
        if (empty($customer->cr_number)) {
            return response()->json([
                'success' => false,
                'message' => 'Customer does not have a CR number (required for SIMAH).',
            ], 400);
        }

        $idNumber = $customer->cr_number;
        $latest = $request->input('latest', false);

        // 4. Check for existing report if 'latest' is false
        $existingReport = SimahReport::where('user_id', $userId)
            ->where('type', 'silverReport')
            ->first();

        if ($existingReport && !$latest) {
            return response()->json([
                'success' => true,
                'payload' => $existingReport->report_json,
                'source'  => 'database',
            ]);
        }

        // 5. Build data for SIMAH request using model data
        // NOTE: Adjust the following data source mappings based on your actual database schema
        $user = $customer->user;
        $data = [
            'idNumber'    => $idNumber,
            'nationality' => 196, // Saudi (Assumed default)

            // Attempt to retrieve names from User model, providing defaults if null
            'firstName'   => $user->first_name ?? 'N/A',
            'secondName'  => $user->last_name ?? 'N/A',
            'familyName'  => $user->family_name ?? 'ABC', // Use a default if family_name isn't standard in your 'users' table
            'thirdName'   => $user->third_name ?? 'CCD', // Use a default if third_name isn't standard in your 'users' table

            // Attempt to retrieve DOB and Expiry from User/Customer data
            // Dates should ideally be in SIMAH's required format (e.g., dd/mm/yyyy)
            'expiryDate'  => $user->id_expiry_date ?? $customer->id_expiry_date ?? '30/10/2040',
            'dateOfBirth' => $user->date_of_birth ?? $customer->date_of_birth ?? '30/11/1970',

            'gender'      => $user->gender ?? 1, // 1 for Male, 2 for Female (Assumed default)
            'memberRefNo' => Str::random(32),
        ];

        // 6. Fetch new report
        try {
            $response = $simahService->getSilverReport($data);

            // 7. Store/update in DB
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
        } catch (\Exception $e) {
            Log::error('Fetch SIMAH failed: ' . $e->getMessage(), [
                'exception'    => $e,
                'user_id'      => $userId,
                'request_data' => $data,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SIMAH data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
