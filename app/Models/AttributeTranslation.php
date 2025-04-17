<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attribute_id',
        'locale',
        'name',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
