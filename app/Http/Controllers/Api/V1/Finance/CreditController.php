<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\CreditLimitResource;
use App\Http\Resources\Finance\SchedulePaymentResource;
use App\Models\CustomerCreditLimit;
use App\Models\SchedulePayment;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\CreditAssessmentService;
use App\Services\RiskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function __construct(
        protected CreditAssessmentService $creditAssessment,
        protected RiskService $riskService,
        protected AuditTrailService $auditTrail
    ) {}

    public function profiles(Request $request): JsonResponse
    {
        $query = User::where('user_type', 'customer')
            ->with(['customerCreditLimit', 'orders' => fn ($q) => $q->where('delivery_status', 'delivered')]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 10), 100);
        $customers = $query->paginate($perPage);

        $data = $customers->map(function ($user) {
            $creditLimit = $user->customerCreditLimit;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'business_name' => $user->business_name,
                'credit_limit' => $creditLimit?->limit_arabianpay_after ?? 0,
                'orders_count' => $user->orders->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function limits(Request $request): JsonResponse
    {
        $query = CustomerCreditLimit::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%"));
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $limits = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CreditLimitResource::collection($limits),
            'meta' => [
                'current_page' => $limits->currentPage(),
                'last_page' => $limits->lastPage(),
                'per_page' => $limits->perPage(),
                'total' => $limits->total(),
            ],
        ]);
    }

    public function repaymentSchedule(Request $request): JsonResponse
    {
        $query = SchedulePayment::with(['user', 'assigned']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%"));
        }

        $perPage = min((int) $request->get('per_page', 10), 100);
        $payments = $query->orderBy('due_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => SchedulePaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function assessment(User $customer): JsonResponse
    {
        $creditScore = $this->creditAssessment->getCreditScore($customer->id);
        $riskScore = $this->riskService->getRiskScore($customer->id);

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                ],
                'credit_score' => $creditScore,
                'risk_score' => $riskScore,
            ],
        ]);
    }

    public function updateLimit(Request $request, User $customer): JsonResponse
    {
        $validated = $request->validate([
            'credit_limit' => 'required|numeric|min:0',
            'reason' => 'required|string|max:1000',
        ]);

        $creditLimit = CustomerCreditLimit::firstOrNew(['user_id' => $customer->id]);
        $creditLimit->limit_arabianpay_after = $validated['credit_limit'];
        $creditLimit->save();

        return response()->json([
            'success' => true,
            'data' => new CreditLimitResource($creditLimit->fresh()->load('user')),
            'message' => 'Credit limit updated',
        ]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->get('search', '');

        $customers = User::where('user_type', 'customer')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            }))
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone_number']);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }
}
