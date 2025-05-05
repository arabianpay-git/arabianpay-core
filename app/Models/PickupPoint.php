<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupPoint extends Model
{
    use HasFactory;

    protected $table = 'pickup_points';

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'phone_number',
        'pick_up_status',
        'cash_on_pickup_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
