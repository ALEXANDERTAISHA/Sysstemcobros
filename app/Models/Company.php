<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['name', 'code', 'color', 'is_active', 'logo_path'];

    protected $casts = ['is_active' => 'boolean'];

    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class);
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            return asset('storage/' . ltrim($this->logo_path, '/'));
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->code ?: $this->name) . '&background=0d6efd&color=ffffff&size=128&bold=true';
    }
}
