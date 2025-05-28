<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BusinessType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'risk_level',
        'order_level',
        'banner',
        'icon',
        'featured'
    ];

    protected array $translatable = [
        'name',
    ];

    protected static function booted()
    {
        static::saving(function ($businessType) {
            if (empty($businessType->slug) || $businessType->isDirty('name')) {
                $slug = Str::slug($businessType->name);
                $originalSlug = $slug;
                $counter = 1;

                while (self::where('slug', $slug)->where('id', '!=', $businessType->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }

                $businessType->slug = $slug;
            }
        });
    }

    public function translations()
    {
        return $this->hasMany(BusinessTypeTranslation::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function merchant()
    {
        return $this->hasMany(Merchant::class);
    }
}
