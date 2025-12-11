<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Actions\GeneratePasskeyRegisterOptionsAction;
use App\Actions\StorePasskeyAction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Exceptions\InvalidPasskey;
use Spatie\LaravelPasskeys\Models\Passkey;

class PasskeyController extends Controller
{
    /**
     * Show passkey registration options.
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        $publicKey = app(GeneratePasskeyRegisterOptionsAction::class)
            ->execute($user, true);

        session(['passkey_registration_options' => json_encode($publicKey)]);

        return view('passkeys.register', [
            'publicKey' => $publicKey,
        ]);
    }

    /**
     * Store a new passkey registration.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            Log::error('Passkey registration attempt with no logged in user');
            return response('You must be logged in to register a passkey.', 403);
        }

        $passkeyJson = $request->input('passkeyJson');
        $passkeyOptionsJson = $request->input('passkeyOptionsJson');
        if (! $passkeyJson || ! $passkeyOptionsJson) {
            return response('Missing passkey data or options.', 400);
        }

        $hostName = parse_url(config('app.url'), PHP_URL_HOST);

        try {
            Log::info('Passkey store attempt for user', ['user_id' => $user->id, 'phone' => $user->phone_number]);

            app(StorePasskeyAction::class)->execute(
                $user,
                $passkeyJson,
                $passkeyOptionsJson,
                $hostName
            );

            session()->forget('passkey_registration_options');

            return redirect()->route('dashboard')->with('success', 'Passkey registered successfully');
        } catch (\Throwable $e) {
            Log::error('Passkey registration failed:', [
                'message' => $e->getMessage(),
                'exception' => $e,
                'passkeyJson' => $passkeyJson,
                'passkeyOptionsJson' => $passkeyOptionsJson,
            ]);
            return response('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show the login page for passkey authentication.
     */
    public function login(): View
    {
        return view('passkeys.login');
    }

    /**
     * Get public key options for passkey authentication by email.
     * IMPORTANT: This method doesn't log anyone in. It just prepares options for the given email.
     */
    public function getPublicKey(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Only find the user - DO NOT log them in yet
        $user = User::whereEncrypted('email', $request->email)->first();
        if (! $user) {
            return response()->json([
                'error' => 'User not found',
                'hasPasskeys' => false
            ], 404);
        }

        $base64url = fn(string $data): string => rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );

        $allowCredentials = $user->passkeys->map(fn(Passkey $p) => [
            'type' => 'public-key',
            'id'   => $base64url($p->data->publicKeyCredentialId),
        ])->values()->all();

        if (empty($allowCredentials)) {
            Log::warning("{$user->email} has no passkeys");
            return response()->json([
                'error' => 'No passkeys found for this user',
                'hasPasskeys' => false
            ], 400);
        }

        $publicKeyJson = app(GeneratePasskeyAuthenticationOptionsAction::class)
            ->execute($user, true);

        $publicKey = json_decode($publicKeyJson, true);

        $publicKey['rpId']             = $publicKey['rpId']
            ?? parse_url(config('app.url'), PHP_URL_HOST);
        $publicKey['allowCredentials'] = $allowCredentials;
        $publicKey['timeout']          = $publicKey['timeout'] ?? 60000;

        // Store user ID for verification in authenticate method
        $publicKey['user_id'] = $user->id;
        $publicKey['email'] = $user->email;

        // store for later verification
        session([
            'passkey_authentication_options' => $publicKey,
            'expected_user_id' => $user->id
        ]);

        return response()->json($publicKey);
    }

    /**
     * Verify the assertion and authenticate user.
     * IMPORTANT: This verifies that the authenticated user matches the one we expected.
     */
    public function authenticate(Request $request)
    {
        try {
            $json = (string) $request->getContent();
            $json = trim($json, "\xEF\xBB\xBF");

            $decoded = json_decode($json, true);
            if (! is_array($decoded)) {
                Log::error('PasskeyController@authenticate: Invalid payload', [
                    'raw_content' => $json,
                    'decoded'     => $decoded,
                ]);
                return response('Invalid request format', 400);
            }

            $cleanJson = json_encode(
                $decoded,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );

            $options = session('passkey_authentication_options', []);
            $expectedUserId = session('expected_user_id');

            if (empty($options) || !$expectedUserId) {
                Log::error('Passkey authentication attempted without proper session state');
                return response('Authentication session expired. Please try again.', 401);
            }

            $passkeyOptionsJson = json_encode(
                $options,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );

            $passkey = app(FindPasskeyToAuthenticateAction::class)
                ->execute($cleanJson, $passkeyOptionsJson);

            $user = User::find($passkey->authenticatable_id);

            // CRITICAL: Verify the authenticated user matches the expected user
            if (!$user || $user->id !== $expectedUserId) {
                Log::error('Passkey authentication user mismatch', [
                    'expected_user_id' => $expectedUserId,
                    'authenticated_user_id' => $user?->id,
                    'passkey_id' => $passkey->id,
                ]);
                return response('Authentication failed: User mismatch.', 401);
            }

            Log::info('Passkey authentication successful', [
                'passkey_id' => $passkey->id,
                'user_id'    => $user->id,
                'email'      => $user->email,
            ]);

            Auth::login($user);

            // Clear session data
            session()->forget(['passkey_authentication_options', 'expected_user_id']);

            return redirect()->route('dashboard')->with('success', 'Logged in with passkey');
        } catch (InvalidPasskey $e) {
            return response('Authentication failed: Invalid passkey. ' . $e->getMessage(), 401);
        } catch (\Throwable $e) {
            Log::error('PasskeyController@authenticate error', ['exception' => $e]);
            return response('Authentication failed: ' . $e->getMessage(), 500);
        }
    }

    public function manage(Request $request): View
    {
        $user = $request->user();
        $passkeys = $user->passkeys()->latest()->get();

        return view('passkeys.manage', compact('passkeys'));
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $passkey = $user->passkeys()->findOrFail($id);
        $passkey->delete();

        return back()->with('success', 'Passkey removed successfully.');
    }

    public function getRegistrationOptions(Request $request)
    {
        $user = $request->user();

        $publicKey = app(GeneratePasskeyRegisterOptionsAction::class)->execute($user, true);

        session(['passkey_registration_options' => json_encode($publicKey)]);

        return response()->json(json_decode($publicKey, true));
    }
}
