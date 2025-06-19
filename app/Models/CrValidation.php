<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrValidation extends Model
{
    protected $table = "cr_validations";

    protected $fillable = [
        'cr_data',
        'email'
    ];
}
