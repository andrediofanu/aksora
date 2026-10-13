<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pencatat aktivitas penting admin/guru (dan aksi sensitif lain).
 */
class AuditLogger
{
    public static function log(
        string $action,
        ?string $description = null,
        ?object $model = null,
        array $meta = [],
    ): void {
        $user = Auth::user();
        $request = app('request');

        $ip = $request instanceof Request ? $request->ip() : null;
        $agent = $request instanceof Request ? (string) $request->userAgent() : null;

        \App\Models\AuditLog::create([
            'user_id' => $user?->getAuthIdentifier(),
            'action' => $action,
            'auditable_type' => $model ? $model->getMorphClass() : null,
            'auditable_id' => $model?->getKey(),
            'description' => $description,
            'ip_address' => $ip,
            'user_agent' => $agent ? \Illuminate\Support\Str::limit($agent, 300, '') : null,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
