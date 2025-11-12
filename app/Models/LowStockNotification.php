<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LowStockNotification extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'emails_sent',
        'last_email_sent_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
