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

        // Keep same shape as before (json-encoded)
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
     *
     * SECURITY CHANGE:
     * - We store the target user's id in session ('passkey_authentication_user_id')
     *   so the subsequent authenticate() call can confirm that the assertion
     *   maps to that same user.
     */
    public function getPublicKey(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::whereEncrypted('email', $request->email)->first();
        if (! $user) {
            return response('User not found', 404);
        }

        $base64url = fn(string $data): string => rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );

        $allowCredentials = $user->passkeys->map(fn(Passkey $p) => [
            'type' => 'public-key',
            // ensure this is base64url of the credential id bytes
            'id'   => $base64url($p->data->publicKeyCredentialId),
        ])->values()->all();

        if (empty($allowCredentials)) {
            Log::warning("{$user->email} has no passkeys");
        } else {
            Log::info('AllowCredentials for passkey login request', [
                'email' => $user->email,
                'allowCredentials_count' => count($allowCredentials),
            ]);
        }

        $publicKeyJson = app(GeneratePasskeyAuthenticationOptionsAction::class)
            ->execute($user, true);

        $publicKey = json_decode($publicKeyJson, true);

        $publicKey['rpId']             = $publicKey['rpId']
            ?? parse_url(config('app.url'), PHP_URL_HOST);
        $publicKey['allowCredentials'] = $allowCredentials;
        $publicKey['timeout']          = $publicKey['timeout'] ?? 60000;

        // store for later verification
        // SECURITY: store both the options AND the intended user id
        session([
            'passkey_authentication_options' => $publicKey,
            'passkey_authentication_user_id' => $user->id,
            'passkey_authentication_user_email' => $user->email, // helpful for logging/debug
        ]);

        return response()->json($publicKey);
    }

    /**
     * Verify the assertion and authenticate user.
     *
     * SECURITY CHANGES:
     * 1. We ensure a stored session user id exists for this authentication attempt.
     * 2. After FindPasskeyToAuthenticateAction returns the matched Passkey, we
     *    check the passkey->authenticatable_id == session user id. If not, reject.
     * 3. We clear the session passkey_authentication_* keys after success/failure to avoid reuse.
     * 4. Return JSON response (success/failure) so XHR clients handle redirects explicitly.
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
            $sessionUserId = session('passkey_authentication_user_id', null);

            if (! $sessionUserId) {
                Log::warning('Passkey authentication attempt without stored session user id', [
                    'session_options_present' => !empty($options),
                ]);
                return response('Authentication failed: session expired or not initialized.', 400);
            }

            $passkeyOptionsJson = json_encode(
                $options,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );

            $passkey = app(FindPasskeyToAuthenticateAction::class)
                ->execute($cleanJson, $passkeyOptionsJson);

            if (! $passkey) {
                Log::warning('PasskeyController@authenticate: No passkey found from assertion', [
                    'session_user_id' => $sessionUserId,
                ]);
                // clear session keys to avoid replay attempts
                session()->forget(['passkey_authentication_options', 'passkey_authentication_user_id', 'passkey_authentication_user_email']);
                return response('Authentication failed: no matching passkey found.', 401);
            }

            // Ensure the passkey belongs to the same user we created the challenge for
            if ((int) $passkey->authenticatable_id !== (int) $sessionUserId) {
                Log::warning('Passkey used does not belong to the user that requested the challenge', [
                    'session_user_id' => $sessionUserId,
                    'passkey_user_id' => $passkey->authenticatable_id,
                    'passkey_id' => $passkey->id,
                ]);

                // clear session keys immediately
                session()->forget(['passkey_authentication_options', 'passkey_authentication_user_id', 'passkey_authentication_user_email']);

                return response('Authentication failed: passkey does not belong to the requested user.', 403);
            }

            $user = User::find($passkey->authenticatable_id);
            Log::info('Passkey authentication successful', [
                'passkey_id' => $passkey->id,
                'user_id'    => $user?->id,
                'user_email' => $user?->email,
                'phone'      => $user?->phone_number,
            ]);

            if (! $user) {
                session()->forget(['passkey_authentication_options', 'passkey_authentication_user_id', 'passkey_authentication_user_email']);
                return response('Authentication failed: No user associated with this passkey.', 500);
            }

            Auth::login($user);

            // Clean up session tokens after success
            session()->forget(['passkey_authentication_options', 'passkey_authentication_user_id', 'passkey_authentication_user_email']);

            // Return JSON for JS to handle redirection
            return response()->json([
                'message' => 'Logged in with passkey',
                'redirect' => route('dashboard'),
            ]);
        } catch (InvalidPasskey $e) {
            Log::warning('InvalidPasskey during authentication', ['message' => $e->getMessage()]);
            // clear session keys to avoid reuse
            session()->forget(['passkey_authentication_options', 'passkey_authentication_user_id', 'passkey_authentication_user_email']);
            return response('Authentication failed: Invalid passkey. ' . $e->getMessage(), 401);
        } catch (\Throwable $e) {
            Log::error('PasskeyController@authenticate error', ['exception' => $e]);
            // clear session keys to avoid reuse
            session()->forget(['passkey_authentication_options', 'passkey_authentication_user_id', 'passkey_authentication_user_email']);
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
