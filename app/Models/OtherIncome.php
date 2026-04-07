<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtherIncome extends Model
{
    protected $fillable = ['income_date', 'description', 'amount', 'client_id', 'credit_id', 'notes'];

    protected $casts = [
        'amount'      => 'decimal:2',
        'income_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }
}
