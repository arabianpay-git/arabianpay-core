<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'locale',
        'name',
        'unit',
        'reson_reject',
        'meta_title',
        'meta_description',
        'short_description',
        'description',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
