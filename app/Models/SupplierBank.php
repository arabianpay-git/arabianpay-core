<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierBank extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_name',
        'iban',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
