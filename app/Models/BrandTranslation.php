<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['locale', 'name', 'meta_title', 'meta_description'];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
