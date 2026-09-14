<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    protected $fillable = ['store_id', 'opened_by', 'closed_by', 'session_number', 'opened_at', 'closed_at', 'opening_cash', 'expected_cash', 'closing_cash', 'cash_difference', 'status', 'notes'];
    protected function casts(): array { return ['opened_at' => 'datetime', 'closed_at' => 'datetime', 'opening_cash' => 'decimal:2', 'expected_cash' => 'decimal:2', 'closing_cash' => 'decimal:2', 'cash_difference' => 'decimal:2']; }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function opener(): BelongsTo { return $this->belongsTo(User::class, 'opened_by'); }
    public function closer(): BelongsTo { return $this->belongsTo(User::class, 'closed_by'); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
}
