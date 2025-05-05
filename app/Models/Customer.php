<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'id_number',
        'id_owner',
        'cr_number',
        'tax_number',
        'cr_data',
        'check_nafath',
        'nafath_data',
        'date_of_birth',
        'purchasing_volume',
        'purchasing_natures',
        'other_purchasing_natures',
        'status',
    ];

    protected $casts = [
        'cr_data' => 'array',
        'check_nafath' => 'boolean',
        'nafath_data' => 'boolean',
        'date_of_birth' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id', 'user_id');
    }

    public function getTotalOrderAmountAttribute()
    {
        $total = 0;

        foreach ($this->orders as $order) {
            $details = json_decode($order->product_details, true);

            if (!is_array($details)) continue;

            foreach ($details as $item) {
                $itemTotal = 0;

                if (!empty($item['attributes'])) {
                    foreach ($item['attributes'] as $attribute) {
                        $itemTotal += $attribute['price'] ?? 0;
                    }
                } elseif (isset($item['price'])) {
                    $itemTotal += $item['price'];
                }

                $total += $itemTotal * ($item['quantity'] ?? 1);
            }
        }

        return $total;
    }
}
