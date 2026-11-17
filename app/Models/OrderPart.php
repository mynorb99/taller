<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPart extends Model
{
    protected $fillable = [
        'product_id', 'name', 'category', 'quantity', 'unit_price', 'inventory_deducted_quantity',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'inventory_deducted_quantity' => 'integer',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
