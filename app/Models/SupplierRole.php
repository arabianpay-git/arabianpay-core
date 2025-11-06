<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierRole extends Model
{
    use HasFactory;

    protected $table = 'supplier_roles';

    protected $fillable = [
        'name',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];
}
