<?php

use App\Models\ActivityLog;

/**
 * Log an activity entry. Wrapped in try/catch so it never breaks the app.
 */
function logActivity(string $action, string $description, $subject = null, array $properties = []): void
{
    try {
        $user = auth()->user();

        ActivityLog::create([
            'user_id'      => $user?->id,
            'tenant_id'    => app()->bound('tenant') ? app('tenant')?->id : ($user?->tenant_id),
            'action'       => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id'   => $subject?->id ?? null,
            'description'  => mb_substr($description, 0, 500),
            'properties'   => $properties ?: null,
            'ip_address'   => request()->ip(),
            'user_agent'   => mb_substr((string) request()->userAgent(), 0, 500),
            'created_at'   => now(),
        ]);
    } catch (\Throwable $e) {
        \Log::warning('Activity log failed: ' . $e->getMessage());
    }
}
