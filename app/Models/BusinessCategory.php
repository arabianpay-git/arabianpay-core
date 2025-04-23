<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BusinessCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'order_level',
        'banner',
        'icon',
        'featured',
    ];

    protected array $translatable = ['name'];

    public function translations()
    {
        return $this->hasMany(BusinessCategoryTranslation::class);
    }

    protected static function booted()
    {
        static::saving(function ($businessCategory) {
            if (empty($businessCategory->slug) || $businessCategory->isDirty('name')) {
                $slug = Str::slug($businessCategory->name);
                $originalSlug = $slug;
                $counter = 1;

                while (self::where('slug', $slug)->where('id', '!=', $businessCategory->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }

                $businessCategory->slug = $slug;
            }
        });
    }
}
