<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    public const TYPE_GENERAL = 'general';
    public const TYPE_EXPENSE_DEBIT = 'expense_debit';

    protected $fillable = ['name', 'code', 'color', 'is_active', 'logo_path', 'company_type'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeOfType($query, string $type)
    {
        if (! Schema::hasColumn($query->getModel()->getTable(), 'company_type')) {
            return $query;
        }

        return $query->where('company_type', $type);
    }

    public function scopeOrderByBusinessList($query)
    {
        $query->orderByRaw("CASE
            WHEN ((LOWER(name) LIKE '%via%america%' OR LOWER(name) LIKE '%vias%america%' OR LOWER(name) LIKE '%v.%america%')
                AND (LOWER(name) LIKE '%transfer%' OR LOWER(name) LIKE '%envio%' OR LOWER(name) LIKE '%envíos%')) THEN 1
            WHEN ((LOWER(name) LIKE '%via%america%' OR LOWER(name) LIKE '%vias%america%' OR LOWER(name) LIKE '%v.%america%')
                AND LOWER(name) LIKE '%tarjeta%') THEN 2
            WHEN ((LOWER(name) LIKE '%via%america%' OR LOWER(name) LIKE '%vias%america%' OR LOWER(name) LIKE '%v.%america%')
                AND LOWER(name) LIKE '%cheque%') THEN 3
            WHEN (LOWER(name) LIKE '%ria%' AND (LOWER(name) LIKE '%transfer%' OR LOWER(name) LIKE '%envio%' OR LOWER(name) LIKE '%envíos%')) THEN 4
            WHEN (LOWER(name) LIKE '%ria%' AND LOWER(name) LIKE '%servicio%') THEN 5
            WHEN (LOWER(name) LIKE '%wester union%' AND (LOWER(name) LIKE '%transfer%' OR LOWER(name) LIKE '%envio%' OR LOWER(name) LIKE '%envíos%')) THEN 6
            WHEN (LOWER(name) LIKE '%nacional%' AND (LOWER(name) LIKE '%transfer%' OR LOWER(name) LIKE '%envio%' OR LOWER(name) LIKE '%envíos%')) THEN 7
            WHEN (LOWER(name) LIKE '%nacional%' AND LOWER(name) LIKE '%tarjeta%') THEN 8
            WHEN (LOWER(name) LIKE '%nacional%' AND LOWER(name) LIKE '%cheque%') THEN 9
            WHEN LOWER(name) LIKE '%tienda%' THEN 10
            WHEN LOWER(name) LIKE '%recarga%' OR LOWER(name) LIKE '%recargas%' THEN 11
            WHEN LOWER(name) LIKE '%paqueteria%' THEN 12
            ELSE 100
        END")
        ->orderBy('name');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class);
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            $normalizedPath = ltrim($this->logo_path, '/');

            if (Storage::disk('public')->exists($normalizedPath)) {
                return route('media.public', ['path' => $normalizedPath], false);
            }
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->code ?: $this->name) . '&background=0d6efd&color=ffffff&size=128&bold=true';
    }
}
