<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    protected $fillable = [
        'company_id',
        'transfer_date',
        'sender_name',
        'receiver_name',
        'amount',
        'commission',
        'transaction_code',
        'status',
        'sent_at',
        'notes',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'commission'    => 'decimal:2',
        'transfer_date' => 'date',
        'sent_at'       => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'sent'      => 'Enviado',
            'pending'   => 'Pendiente',
            'resent'    => 'Reenviado',
            'cancelled' => 'Cancelado',
            default     => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'sent'      => 'success',
            'pending'   => 'warning',
            'resent'    => 'info',
            'cancelled' => 'danger',
            default     => 'secondary',
        };
    }
}
