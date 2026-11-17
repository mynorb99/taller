<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    protected $fillable = [
        'code', 'client_id', 'client', 'phone', 'equipment', 'repair_type', 'notes',
        'status', 'received_at', 'quoted_at', 'quote_data', 'ready_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'quoted_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'quote_data' => 'array',
        ];
    }

    public function parts(): HasMany
    {
        return $this->hasMany(OrderPart::class);
    }

    public function registeredClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function laborItems(): HasMany
    {
        return $this->hasMany(LaborItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->parts->sum(fn (OrderPart $part) => $part->quantity * (float) $part->unit_price)
            + (float) $this->laborItems->sum('amount');
    }

    public function getPaidAttribute(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function getBalanceAttribute(): float
    {
        return $this->total - $this->paid;
    }
}
