<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $fillable = ['store_id', 'product_id', 'quantity', 'reserved_quantity'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'reserved_quantity' => 'decimal:3']; }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
