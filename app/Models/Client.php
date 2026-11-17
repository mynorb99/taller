<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'notes'];

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return $this->workOrders
            ->whereNotIn('status', ['reception', 'quoted'])
            ->sum(fn (WorkOrder $order) => max($order->balance, 0));
    }

    public function getPendingOrdersAttribute(): Collection
    {
        return $this->workOrders
            ->filter(fn (WorkOrder $order) => ! in_array($order->status, ['reception', 'quoted'], true)
                && $order->balance > 0.001)
            ->values();
    }
}
