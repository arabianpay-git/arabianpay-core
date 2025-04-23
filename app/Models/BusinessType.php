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
            $nameEn = is_array($businessType->name) ? ($businessType->name['en'] ?? '') : $businessType->name;

            if (empty($businessType->slug) || $businessType->isDirty('name')) {
                $slug = Str::slug($nameEn);
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
}
