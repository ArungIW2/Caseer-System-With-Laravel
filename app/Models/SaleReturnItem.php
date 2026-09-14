<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    protected $fillable = ['sale_return_id', 'sale_item_id', 'quantity', 'refund_unit_price', 'refund_total', 'restock'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'refund_unit_price' => 'decimal:2', 'refund_total' => 'decimal:2', 'restock' => 'boolean']; }
    public function saleReturn(): BelongsTo { return $this->belongsTo(SaleReturn::class); }
    public function saleItem(): BelongsTo { return $this->belongsTo(SaleItem::class); }
}
