<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCategoryTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_category_id',
        'locale',
        'name',
    ];
}
