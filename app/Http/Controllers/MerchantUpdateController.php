<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class MerchantUpdateController extends Controller
{
    public function index()
    {
        $users = User::where('user_type', 'merchant')
            ->whereNull('main_user_id')
            ->paginate(10);

        return view('admin.merchants.index', compact('users'));
    }

    public function search(Request $request)
    {
        $query = $request->input('query', '');

        // 💡 Change: Original logic restored. Fetch all relevant users first.
        $users = User::where('user_type', 'merchant')
            ->whereNull('main_user_id')
            ->get();

        // 💡 Change: Filter the collection as in your original code.
        $filtered = $users->filter(function ($user) use ($query) {
            $q = strtolower($query);
            return str_contains(strtolower($user->first_name ?? ''), $q)
                || str_contains(strtolower($user->last_name ?? ''), $q)
                || str_contains(strtolower($user->business_name ?? ''), $q)
                || str_contains(strtolower($user->email ?? ''), $q)
                || str_contains(strtolower($user->phone_number ?? ''), $q);
        });

        // 💡 Change: Use pagination on the filtered collection.
        $page = (int) $request->input('page', 1);
        $perPage = 10;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            [
                'path' => route('merchants.search'),
                'query' => $request->query(),
            ]
        );

        if ($request->ajax()) {
            return view('admin.merchants.partials.table', ['users' => $paginated])->render();
        }

        return view('admin.merchants.index', ['users' => $paginated]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
        ]);

        $user = User::where('user_type', 'merchant')
            ->whereNull('main_user_id')
            ->findOrFail($id);

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'business_name' => $request->business_name,
        ]);

        if ($request->ajax()) {
            // This part is the same as the previous response.
            // It gets the current page from the request to send it back.
            $currentPage = (int) $request->input('page', 1);
            return response()->json([
                'success' => true,
                'message' => 'Merchant updated successfully.',
                'page' => $currentPage
            ]);
        }

        return redirect()->route('merchants.index')->with('success', 'Merchant updated successfully.');
    }
}
