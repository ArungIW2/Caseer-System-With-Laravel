<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = ['store_id', 'supplier_id', 'created_by', 'purchase_number', 'purchased_at', 'status', 'subtotal', 'discount_total', 'tax_total', 'grand_total', 'notes'];
    protected function casts(): array { return ['purchased_at' => 'datetime', 'subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'tax_total' => 'decimal:2', 'grand_total' => 'decimal:2']; }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(PurchaseItem::class); }
}
