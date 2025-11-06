<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DunningTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'dpd_bucket',
        'type',
        'language',
        'throttling',
        'message',
    ];
}
