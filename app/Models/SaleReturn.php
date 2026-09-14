<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    protected $fillable = ['store_id', 'sale_id', 'processed_by', 'return_number', 'returned_at', 'status', 'refund_total', 'reason'];
    protected function casts(): array { return ['returned_at' => 'datetime', 'refund_total' => 'decimal:2']; }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
    public function items(): HasMany { return $this->hasMany(SaleReturnItem::class); }
}
