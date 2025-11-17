<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimahReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'report_json',
    ];

    protected $casts = [
        'report_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
