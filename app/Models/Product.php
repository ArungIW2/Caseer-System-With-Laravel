<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['sku', 'barcode', 'name', 'description', 'category_id', 'brand_id', 'unit_id', 'cost_price', 'selling_price', 'minimum_stock', 'track_stock', 'is_active'];
    protected function casts(): array { return ['cost_price' => 'decimal:2', 'selling_price' => 'decimal:2', 'minimum_stock' => 'decimal:3', 'track_stock' => 'boolean', 'is_active' => 'boolean']; }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
    public function inventories(): HasMany { return $this->hasMany(Inventory::class); }
}
