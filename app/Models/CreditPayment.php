<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPayment extends Model
{
    protected $fillable = ['credit_id', 'amount', 'payment_date', 'notes'];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }
}
