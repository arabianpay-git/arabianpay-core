<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Merchant extends Model
{
    protected $fillable = [
        'user_id',
        'business_type_id',
        'business_category_id',
        'goverment_data',
        'cr_number',
        'registration_number_form',
        'vat_register',
        'vat_register_number',
        'vat_register_file',
        'return_policy',
        'return_day_count',
        'return_policy_file',
        'exchange_policy',
        'exchange_day_count',
        'exchange_policy_file',
        'cancel_policy',
        'cancel_day_count',
        'cancel_policy_file',
        'owner_name',
        'owner_iqama_number',
        'owner_iqama_image',
        'term_status',
        'status',
    ];

    /**
     * User relation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Business Type relation
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * Accessor for business category IDs as array
     */
    public function getBusinessCategoryIdsAttribute()
    {
        return explode(',', $this->business_category_id);
    }
}
