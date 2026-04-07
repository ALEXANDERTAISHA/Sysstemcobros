<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'whatsapp', 'address', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function otherIncomes(): HasMany
    {
        return $this->hasMany(OtherIncome::class);
    }

    public function getTotalDebtAttribute(): float
    {
        return $this->credits()
            ->whereIn('status', ['active', 'partial'])
            ->sum(\Illuminate\Support\Facades\DB::raw('total_amount - paid_amount'));
    }
}
