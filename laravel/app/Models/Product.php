<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['name', 'category', 'cost', 'price', 'stock', 'minimum_stock'];

    protected function casts(): array
    {
        return ['cost' => 'decimal:2', 'price' => 'decimal:2'];
    }

    public function parts(): HasMany
    {
        return $this->hasMany(OrderPart::class);
    }
}
