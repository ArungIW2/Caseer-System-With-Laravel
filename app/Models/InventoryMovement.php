<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = ['store_id', 'product_id', 'user_id', 'reference_type', 'reference_id', 'movement_type', 'quantity', 'unit_cost', 'quantity_before', 'quantity_after', 'reason', 'occurred_at'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'quantity_before' => 'decimal:3', 'quantity_after' => 'decimal:3', 'occurred_at' => 'datetime']; }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
