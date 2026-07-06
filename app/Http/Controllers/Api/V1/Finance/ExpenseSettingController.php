<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\ExpenseSettingResource;
use App\Models\ExpenseSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $expenseSettings = ExpenseSetting::with('creditAccount')->get();

        return response()->json([
            'success' => true,
            'data' => ExpenseSettingResource::collection($expenseSettings),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refrence_id' => 'required|string|max:255|unique:expense_setting,refrence_id',
            'description' => 'required|string|max:255',
            'amount_type' => 'required|in:fixed,percent',
            'amount' => [
                'required',
                'numeric',
                'min:0',
                $request->input('amount_type') === 'percent' ? 'max:100' : '',
            ],
            'credit_acc_id' => 'required|exists:f_accounts,id',
        ]);

        $expenseSetting = ExpenseSetting::create($validated);
        $expenseSetting->load('creditAccount');

        return response()->json([
            'success' => true,
            'data' => new ExpenseSettingResource($expenseSetting),
            'message' => 'Expense setting created successfully',
        ], 201);
    }

    public function show(ExpenseSetting $expenseSetting): JsonResponse
    {
        $expenseSetting->load('creditAccount');

        return response()->json([
            'success' => true,
            'data' => new ExpenseSettingResource($expenseSetting),
        ]);
    }

    public function update(Request $request, ExpenseSetting $expenseSetting): JsonResponse
    {
        $validated = $request->validate([
            'refrence_id' => 'required|string|max:255|unique:expense_setting,refrence_id,'.$expenseSetting->id,
            'description' => 'required|string|max:255',
            'amount_type' => 'required|in:fixed,percent',
            'amount' => 'required|numeric|min:0',
            'credit_acc_id' => 'required|exists:f_accounts,id',
        ]);

        $expenseSetting->update($validated);
        $expenseSetting->load('creditAccount');

        return response()->json([
            'success' => true,
            'data' => new ExpenseSettingResource($expenseSetting->fresh()),
            'message' => 'Expense setting updated successfully',
        ]);
    }

    public function destroy(ExpenseSetting $expenseSetting): JsonResponse
    {
        $expenseSetting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense setting deleted successfully',
        ]);
    }
}
