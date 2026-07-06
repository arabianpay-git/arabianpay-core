<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (! in_array(Auth::user()->user_type, ['admin', 'employee'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        return $next($request);
    }
}
