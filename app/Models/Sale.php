<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = ['store_id', 'cashier_id', 'cash_session_id', 'invoice_number', 'sold_at', 'status', 'subtotal', 'discount_total', 'tax_total', 'grand_total', 'paid_total', 'change_total', 'notes'];
    protected function casts(): array { return ['sold_at' => 'datetime', 'subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'tax_total' => 'decimal:2', 'grand_total' => 'decimal:2', 'paid_total' => 'decimal:2', 'change_total' => 'decimal:2']; }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function cashier(): BelongsTo { return $this->belongsTo(User::class, 'cashier_id'); }
    public function cashSession(): BelongsTo { return $this->belongsTo(CashSession::class); }
    public function items(): HasMany { return $this->hasMany(SaleItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function returns(): HasMany { return $this->hasMany(SaleReturn::class); }
}
