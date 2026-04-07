<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyClosing extends Model
{
    protected $fillable = [
        'closing_date',
        'total_incomes',
        'total_expenses',
        'value_total',
        'other_incomes_total',
        'sum_total',
        'existing_value',
        'difference',
        'final_total',
        'notes',
    ];

    protected $casts = [
        'closing_date'        => 'date',
        'total_incomes'       => 'decimal:2',
        'total_expenses'      => 'decimal:2',
        'value_total'         => 'decimal:2',
        'other_incomes_total' => 'decimal:2',
        'sum_total'           => 'decimal:2',
        'existing_value'      => 'decimal:2',
        'difference'          => 'decimal:2',
        'final_total'         => 'decimal:2',
    ];
}
