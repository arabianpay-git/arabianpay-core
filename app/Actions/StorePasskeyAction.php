<?php

namespace App\Actions;

use Illuminate\Support\Str;
use Spatie\LaravelPasskeys\Exceptions\InvalidPasskey;
use Spatie\LaravelPasskeys\Exceptions\InvalidPasskeyOptions;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Models\Passkey;
use Spatie\LaravelPasskeys\Support\Serializer;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;

class StorePasskeyAction
{
    public function execute(
        HasPasskeys $authenticatable,
        string $passkeyJson,
        string $passkeyOptionsJson,
        string $hostName,
        array $additionalProperties = [],
    ): Passkey {
        $publicKeyCredentialSource = $this->determinePublicKeyCredentialSource(
            $passkeyJson,
            $passkeyOptionsJson,
            $hostName
        );

        // Ensure 'name' is passed — fallback to browser/device info if not
        if (! isset($additionalProperties['name'])) {
            $userAgent = request()->header('User-Agent', 'Unnamed Device');
            $additionalProperties['name'] = Str::limit($userAgent, 255); // keep within DB limit
        }

        /** @var Passkey $passkey */
        $passkey = $authenticatable->passkeys()->create([
            ...$additionalProperties,
            'data' => $publicKeyCredentialSource,
        ]);

        return $passkey;
    }

    protected function determinePublicKeyCredentialSource(
        string $passkeyJson,
        string $passkeyOptionsJson,
        string $hostName,
    ): PublicKeyCredentialSource {
        $passkeyOptions = $this->getPasskeyOptions($passkeyOptionsJson);

        $publicKeyCredential = $this->getPasskey($passkeyJson);

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw InvalidPasskey::invalidPublicKeyCredential();
        }

        $csmFactory = new CeremonyStepManagerFactory;
        $creationCsm = $csmFactory->creationCeremony();

        try {
            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create($creationCsm)->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: $passkeyOptions,
                host: $hostName,
            );
        } catch (Throwable $exception) {
            throw InvalidPasskey::invalidAuthenticatorAttestationResponse($exception);
        }

        return $publicKeyCredentialSource;
    }

    protected function getPasskeyOptions(string $passkeyOptionsJson): PublicKeyCredentialCreationOptions
    {
        if (! json_validate($passkeyOptionsJson)) {
            throw InvalidPasskeyOptions::invalidJson();
        }

        return Serializer::make()->fromJson(
            $passkeyOptionsJson,
            PublicKeyCredentialCreationOptions::class
        );
    }

    protected function getPasskey(string $passkeyJson): PublicKeyCredential
    {
        if (! json_validate($passkeyJson)) {
            throw InvalidPasskey::invalidJson();
        }

        return Serializer::make()->fromJson(
            $passkeyJson,
            PublicKeyCredential::class
        );
    }
}
