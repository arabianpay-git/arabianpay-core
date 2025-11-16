<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    protected $table = 'checkouts';
    protected $fillable = [
        'uuid',
        'user_id',
        'device_id',
        'pool_id',
    ]; 
    public function user(){ 
        return $this->belongsTo(User::class);
    }
    public function orders(){
        return $this->hasMany(Order::class);
    }
    public function schedulePayments(){
        return $this->hasMany(SchedulePayment::class);
    }
   // checkout->schedulePayments->payments
    public function payments(){
        return $this->hasManyThrough(Payment::class, SchedulePayment::class);
    }
    public function investmentPool(){
        return $this->belongsTo(InvestmentPool::class, 'pool_id');
    }
    public function transaction(){
        return $this->hasOne(Transaction::class);
    }
}