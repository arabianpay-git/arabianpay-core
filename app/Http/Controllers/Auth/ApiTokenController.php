<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Handles API token lifecycle (refresh, revoke).
 *
 * SAMA CSF 3.3.5 / MVC §4.2: API authentication tokens must have a finite
 * lifetime and support secure rotation. Sanctum tokens expire after
 * config('sanctum.expiration') minutes (default 60). The refresh endpoint
 * revokes the presenting token and issues a new one with the same abilities.
 *
 * All token actions are audit-logged via the global AuditLogMiddleware plus
 * an explicit audit-trail entry keyed with event_type=api_token_refresh.
 */
class ApiTokenController extends Controller
{
    /**
     * Refresh the caller's API token.
     *
     * The currently presented token is revoked and a new token with the
     * same abilities is issued. Only valid when the caller authenticated
     * via a personal access token (not the session guard).
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $currentToken = $user->currentAccessToken();

        // currentAccessToken() returns a TransientToken when the user is
        // authenticated via session (SPA) rather than a bearer token.
        if (! $currentToken || ! method_exists($currentToken, 'delete')) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh is only available for bearer-token sessions.',
            ], 422);
        }

        $tokenName = $currentToken->name ?: 'api-token';
        $abilities = $currentToken->abilities ?: ['*'];
        $expirationMinutes = (int) config('sanctum.expiration', 60);
        $expiresAt = $expirationMinutes > 0
            ? Carbon::now()->addMinutes($expirationMinutes)
            : null;

        $newToken = $user->createToken($tokenName, $abilities, $expiresAt);

        $currentToken->delete();

        if (function_exists('audit')) {
            try {
                audit()->log([
                    'event_type' => 'api_token_refresh',
                    'entity_type' => 'personal_access_token',
                    'entity_id' => (string) $newToken->accessToken->id,
                    'action_summary' => 'User refreshed API token',
                    'properties' => [
                        'old_token_id' => $currentToken->id,
                        'new_token_id' => $newToken->accessToken->id,
                        'abilities' => $abilities,
                        'expires_at' => $expiresAt?->toIso8601String(),
                    ],
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
            'expires_in' => $expirationMinutes > 0 ? $expirationMinutes * 60 : null,
        ]);
    }
}
