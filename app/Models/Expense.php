<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['expense_date', 'description', 'amount', 'category', 'notes'];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];
}
