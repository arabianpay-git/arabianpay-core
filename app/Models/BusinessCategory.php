<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BusinessCategory extends Model
{
    use HasFactory;
    use LogsModelActions;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true; // Save only changed attributes
    protected static $logName = 'business_category'; // Custom log name

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
