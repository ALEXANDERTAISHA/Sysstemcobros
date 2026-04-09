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
        return (string) static::whereDate('date', today())->sum('initial_amount');
    }
}
