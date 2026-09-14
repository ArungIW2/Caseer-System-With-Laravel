<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'store_id', 'is_active'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean']; }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class, 'cashier_id'); }
    public function inventoryMovements(): HasMany { return $this->hasMany(InventoryMovement::class); }
}
