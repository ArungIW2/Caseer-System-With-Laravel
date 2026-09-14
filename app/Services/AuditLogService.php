<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public function record(?User $user, string $event, Model $auditable, ?array $oldValues = null, ?array $newValues = null, ?Request $request = null, ?int $storeId = null): AuditLog
    {
        $request ??= request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'store_id' => $storeId ?? $user?->store_id,
            'event' => $event,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
