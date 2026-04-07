<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashBoxInitial extends Model
{
    protected $fillable = ['date', 'initial_amount', 'notes'];

    protected $casts = [
        'date' => 'date',
        'initial_amount' => 'decimal:2',
    ];

    public static function getTodayInitial(): ?string
    {
        return static::whereDate('date', today())->value('initial_amount');
    }
}
