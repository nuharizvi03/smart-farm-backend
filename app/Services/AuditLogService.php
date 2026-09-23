<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogService
{
    /**
     * Create a new audit log entry.
     *
     * Audit logs are intentionally insert-only.
     */
    public function log(
        ?User $user,
        string $actionType,
        ?string $description = null,
        ?array $metadata = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->id,
            'action_type' => $actionType,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}