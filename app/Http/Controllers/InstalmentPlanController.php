<?php

namespace App\Http\Controllers;

use App\Models\InstalmentPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstalmentPlanController extends Controller
{
    public function index(Request $request)
    {
        $instalmentPlans = InstalmentPlan::with('translations')->select('instalment_plans.*')->paginate(10);

        return view('admin.instalment_plans.index', compact('instalmentPlans'));
    }

    public function create()
    {
        return view('admin.instalment_plans.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]*$/', 'unique:instalment_plans,name'],
            'description' => ['nullable', 'string'],
            'duration' => ['required', 'string'],
            'finance_limit' => ['required', 'string'],
            'patch_days' => ['required', 'string'],
            'late_fee' => ['nullable', 'string'],
            'transaction_fee' => ['required', 'string'],
            'installments' => ['required', 'string'],
            'status' => ['required', 'string'],
        ]);

        $instalmentPlan = InstalmentPlan::create([
            'name' => $request->name,
            'description' => $request->description,
            'duration' => $request->duration,
            'finance_limit' => $request->finance_limit,
            'patch_days' => $request->patch_days,
            'late_fee' => $request->late_fee,
            'transaction_fee' => $request->transaction_fee,
            'installments' => $request->installments,
            'status' => $request->status,
        ]);

        return redirect()->route('instalment-plans.index')->with('success', 'Instalment Plan created successfully.');
    }

    public function edit(InstalmentPlan $instalmentPlan)
    {
        return view('admin.instalment_plans.edit', compact('instalmentPlan'));
    }

    public function update(Request $request, InstalmentPlan $instalmentPlan)
    {
        $request->validate([
            'name.en' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]*$/',
                Rule::unique('instalment_plans', 'name')->ignore($instalmentPlan->id),
            ],
            'description.en' => ['nullable', 'string'],
            'duration' => ['required', 'string'],
            'finance_limit' => ['required', 'string'],
            'patch_days' => ['required', 'string'],
            'late_fee' => ['nullable', 'string'],
            'transaction_fee' => ['required', 'string'],
            'installments' => ['required', 'string'],
            'status' => ['required', 'string'],
        ]);

        $instalmentPlan->update([
            'name' => $request->name['en'],
            'description' => $request->description['en'],
            'duration' => $request->duration,
            'finance_limit' => $request->finance_limit,
            'patch_days' => $request->patch_days,
            'late_fee' => $request->late_fee,
            'transaction_fee' => $request->transaction_fee,
            'installments' => $request->installments,
            'status' => $request->status,
        ]);

        $this->storeOrUpdateTranslations($instalmentPlan, $request);

        return redirect()->route('instalment-plans.index')->with('success', 'Instalment Plan updated successfully.');
    }

    public function destroy($id)
    {
        $instalmentPlan = InstalmentPlan::find($id);
        $instalmentPlan->delete();

        return redirect()->route('instalment-plans.index')->with('success', 'Instalment Plan deleted successfully.');
    }

    private function storeOrUpdateTranslations(InstalmentPlan $instalmentPlan, Request $request)
    {
        if ($request->has('name') && isset($request->name['ar'])) {
            $instalmentPlan->translations()->updateOrCreate(
                ['locale' => 'ar'],
                ['name' => $request->name['ar'], 'description' => $request->description['ar']]
            );
        }
    }
}
