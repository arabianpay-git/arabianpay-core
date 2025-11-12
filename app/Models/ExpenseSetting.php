<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseSetting extends Model
{
    use HasFactory;

    protected $table = 'expense_setting';

    protected $fillable = [
        'refrence_id',
        'description',
        'amount_type',
        'amount',
        'credit_acc_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the credit account associated with this expense setting.
     */
    public function creditAccount()
    {
        return $this->belongsTo(FAccounts::class, 'credit_acc_id', 'id');
    }

    /**
     * Get formatted amount based on type.
     */
    public function getFormattedAmountAttribute()
    {
        if ($this->amount_type === 'percent') {
            return number_format((float)$this->amount, 2) . '%';
        }
        return number_format((float)$this->amount, 2);
    }
}
