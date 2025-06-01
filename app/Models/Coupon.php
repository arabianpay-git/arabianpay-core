<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Coupon extends Model
{
    use HasFactory;
    use LogsModelActions;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true; // Save only changed attributes
    protected static $logName = 'coupon'; // Custom log name

    protected $fillable = [
        'user_id',
        'type',
        'owner',
        'added_by',
        'code',
        'slug',
        'details',
        'discount',
        'discount_type',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'details' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($coupon) {
            if (empty($coupon->slug) || $coupon->isDirty('code')) {
                $slug = Str::slug($coupon->code);
                $originalSlug = $slug;
                $counter = 1;

                while (self::where('slug', $slug)->where('id', '!=', $coupon->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }

                $coupon->slug = $slug;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
