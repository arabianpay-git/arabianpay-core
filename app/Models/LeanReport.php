<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeanReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'report_id',
        'data',
        'status',
        'lean_reference',
        'lean_product',
        'error_message',
    ];

    protected $casts = [
        'data' => 'array',
        'has_data' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeWithData($query)
    {
        // If DB supports has_data generated column, this will be fast. Otherwise it's harmless.
        return $query->where('has_data', 1);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
