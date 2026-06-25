<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Financial Accounts
class FAccounts extends Model
{
    protected $table = 'f_accounts';

    protected $fillable = [
        'id',  // primary key, int
        'account_name',
        'description',
        'account_type1', // int 1 for budget, 2 for non-budget
        'account_type2', // int 1 for debit, 2 for credit
        'status', // e.g., 'active', 'inactive'
    ];

    public function entries()
    {
        return $this->hasMany(FEntry::class, 'account_id', 'id');
    }
}
