<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'last_weight',
        'new_weight',
        'cr_id',
        'pos',
        'repayment',
        'industry',
        'location',
    ];

    protected $casts = [
        'last_weight' => 'array',
        'new_weight' => 'array',
    ];

    // Relation to employee (user)
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
