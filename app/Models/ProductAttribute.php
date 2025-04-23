<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $table = 'product_attribute'; // Specify the table if it's not the plural form of the model name

    protected $fillable = [
        'product_id',
        'attribute_id',
        'attribute_value_id',
    ];

    // Define relationships if necessary
    // For example, if you want to get related product and attribute:
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function attributeValue()
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id');
    }
}
