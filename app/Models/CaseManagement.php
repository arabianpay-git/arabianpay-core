<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseManagement extends Model
{
    protected $table = 'case_management';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'documents',
    ];

    protected $casts = [
        'documents' => 'array',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
