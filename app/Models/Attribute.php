<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    use LogsModelActions;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true; // Save only changed attributes
    protected static $logName = 'attribute'; // Custom log name
    protected $fillable = ['name'];

    protected array $translatable = ['name'];

    public function translations()
    {
        return $this->hasMany(AttributeTranslation::class);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (!in_array($key, $this->translatable)) {
            return $value;
        }

        $locale = app()->getLocale();

        if ($locale === 'en') {
            return $value;
        }

        $translation = $this->translations->where('locale', $locale)->first();

        return $translation?->$key ?? $value;
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attribute')
            ->withPivot('attribute_value_id');
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
