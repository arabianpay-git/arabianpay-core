<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Joelwmale\LaravelEncryption\Builders\EloquentBuilder;
use Joelwmale\LaravelEncryption\Services\EncryptService;
use Joelwmale\LaravelEncryption\Support\HandleCastableAttributes;
use Joelwmale\LaravelEncryption\Support\ParseAttributes;

trait EncryptsAttributes
{
    private $encryptionEnabled;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->encryptionEnabled = config('laravel_encryption.enabled', true);
    }

    public function newEloquentBuilder($query)
    {
        return new EloquentBuilder($query);
    }

    public function getEncryptableAttributes()
    {
        return $this->encryptableAttributes ?? [];
    }

    public static function bootEncryptsAttributes()
    {
        static::saving(function ($model) {
            $model->encryptAttributes();
        });

        static::saved(function ($model) {
            $model->decryptAttributes();
        });

        static::retrieved(function ($model) {
            $model->decryptAttributes();
        });
    }

    public function encryptAttributes()
    {
        if (! $this->encryptionEnabled) {
            return;
        }

        foreach ($this->getEncryptableAttributes() as $attribute) {
            if (! empty($this->$attribute)) {
                $this->$attribute = EncryptService::encrypt(
                    ParseAttributes::parse(
                        $this->encryptableCasts ?? [],
                        $attribute,
                        $this->$attribute
                    )
                );
            }
        }
    }

    public function decryptAttributes()
    {
        if (! $this->encryptionEnabled) {
            return;
        }

        foreach ($this->getEncryptableAttributes() as $attribute) {
            if (! empty($this->$attribute) && $this->attributeIsEncrypted($attribute)) {
                $value = EncryptService::decrypt($this->$attribute);

                if (! empty($this->encryptableCasts)) {
                    $this->$attribute = HandleCastableAttributes::handle($this->encryptableCasts, $attribute, $value);
                } else {
                    $this->$attribute = $value;
                }
            }
        }
    }

    public function attributeIsEncrypted($attribute)
    {
        try {
            EncryptService::decrypt($this->$attribute);
        } catch (\Exception $e) {
            // Only expected decryption failures should return false.
            // Log anything else so it is not silently swallowed.
            if (! $this->looksLikeCiphertext($this->$attribute)) {
                Log::warning('EncryptsAttributes: unexpected value for encrypted attribute', [
                    'attribute' => $attribute,
                    'message' => $e->getMessage(),
                ]);
            }

            return false;
        }

        return true;
    }

    protected function looksLikeCiphertext($value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        // joelwmale/laravel-encryption stores ciphertext as base64 of an OpenSSL payload.
        return preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $value) === 1;
    }

    public function parseAttribute($attribute)
    {
        $castType = $this->encryptableCasts[$attribute] ?? null;

        if ($this->$attribute instanceof \DateTime && $castType === 'datetime') {
            return $this->$attribute->format('Y-m-d H:i:s');
        }

        if ($this->$attribute instanceof \DateTime && $castType === 'date') {
            return $this->$attribute->format('Y-m-d');
        }

        if ($this->$attribute instanceof \DateTime && $castType === 'time') {
            return $this->$attribute->format('H:i:s');
        }

        if ($this->$attribute instanceof \DateTime) {
            return $this->$attribute->format('Y-m-d H:i:s');
        }

        if ($castType === 'json') {
            return json_encode($this->$attribute);
        }

        return $this->$attribute;
    }
}
