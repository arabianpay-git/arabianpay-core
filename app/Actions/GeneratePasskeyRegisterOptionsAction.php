<?php

namespace App\Actions;

use Illuminate\Support\Str;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction as SpatieGeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Support\Config;
use Spatie\LaravelPasskeys\Support\Serializer;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity; // Required for Str::random() if you revert challenge to string

class GeneratePasskeyRegisterOptionsAction extends SpatieGeneratePasskeyRegisterOptionsAction
{
    /**
     * Executes the action to generate passkey registration options.
     * Overrides Spatie's action to include specific pubKeyCredParams and a binary challenge.
     *
     * @param  HasPasskeys  $authenticatable  The user model.
     * @param  bool  $asJson  Whether to return the options as a JSON string.
     */
    public function execute(
        HasPasskeys $authenticatable,
        bool $asJson = true,
    ): string|PublicKeyCredentialCreationOptions {
        // Define the public key credential parameters (cryptographic algorithms)
        $pubKeyCredParams = [
            PublicKeyCredentialParameters::create('public-key', -7),   // ES256 (ECDSA with P-256 and SHA-256)
            PublicKeyCredentialParameters::create('public-key', -257), // RS256 (RSASSA-PKCS1-v1_5 with SHA-256)
            PublicKeyCredentialParameters::create('public-key', -8),   // EdDSA (Edwards-curve Digital Signature Algorithm)
        ];

        // Create the PublicKeyCredentialCreationOptions object
        $options = new PublicKeyCredentialCreationOptions(
            rp: $this->relatedPartyEntity(),
            user: $this->generateUserEntity($authenticatable),
            challenge: $this->challenge(), // Use the overridden challenge method
            pubKeyCredParams: $pubKeyCredParams, // Pass the defined parameters
            timeout: 60000, // Set timeout for the WebAuthn ceremony (60 seconds)
            attestation: 'none', // Set attestation preference to 'none' for simpler setup
            // You can add other options here if needed, e.g., authenticatorSelection, excludeCredentials
            // authenticatorSelection: AuthenticatorSelectionCriteria::create(...),
            // excludeCredentials: $this->getExcludedCredentials($authenticatable),
        );

        // If 'asJson' is true, serialize the options to a JSON string
        if ($asJson) {
            $options = Serializer::make()->toJson($options);
        }

        return $options;
    }

    /**
     * Overrides the parent's challenge method to generate a cryptographically secure binary string.
     * WebAuthn challenges must be random byte sequences, not alphanumeric strings.
     *
     * @return string A cryptographically secure random binary string (32 bytes).
     */
    protected function challenge(): string
    {
        return random_bytes(32);
    }

    // The following methods are inherited from Spatie's base action.
    // If you need to customize them further, you can uncomment and modify them here.

    protected function relatedPartyEntity(): PublicKeyCredentialRpEntity
    {
        return new PublicKeyCredentialRpEntity(
            name: 'ArabianPay',
            id: parse_url(config('app.url'), PHP_URL_HOST),
            icon: null
        );
    }

    // protected function relatedPartyEntity(): PublicKeyCredentialRpEntity
    // {
    //     return new PublicKeyCredentialRpEntity(
    //         name: Config::getRelyingPartyName(),
    //         id: Config::getRelyingPartyId(),
    //         icon: Config::getRelyingPartyIcon(),
    //     );
    // }

    // public function generateUserEntity(HasPasskeys $authenticatable): PublicKeyCredentialUserEntity
    // {
    //     return new PublicKeyCredentialUserEntity(
    //         name: $authenticatable->getPassKeyName(),
    //         id: $authenticatable->getPassKeyId(),
    //         displayName: $authenticatable->getPassKeyDisplayName(),
    //     );
    // }

    // Example for excludeCredentials, uncomment if your user model has passkeys relationship
    /*
    protected function getExcludedCredentials(HasPasskeys $authenticatable): array
    {
        $excludedCredentials = [];
        foreach ($authenticatable->passkeys as $passkey) {
            $excludedCredentials[] = PublicKeyCredentialDescriptor::create('public-key', $passkey->external_id);
        }
        return $excludedCredentials;
    }
    */
}
