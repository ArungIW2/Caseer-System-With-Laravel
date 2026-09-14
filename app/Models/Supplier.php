<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use SoftDeletes;
    protected $fillable = ['code', 'name', 'contact_person', 'phone', 'email', 'address', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function purchases(): HasMany { return $this->hasMany(Purchase::class); }
}
