<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogger
{
    public function log(
        Request $request,
        string $action,
        Model|string|null $subject = null,
        int|string|null $subjectId = null,
        array $metadata = [],
    ): AuditLog {
        [$subjectType, $resolvedSubjectId] = $this->resolveSubject($subject, $subjectId);

        return AuditLog::query()->create([
            'actor_user_id' => $request->user()?->getKey(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $resolvedSubjectId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata === [] ? null : $this->sanitizeMetadata($metadata),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveSubject(Model|string|null $subject, int|string|null $subjectId): array
    {
        if ($subject instanceof Model) {
            return [$subject::class, (string) $subject->getKey()];
        }

        if (is_string($subject)) {
            return [$subject, $subjectId !== null ? (string) $subjectId : null];
        }

        return [null, $subjectId !== null ? (string) $subjectId : null];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata, int $depth = 0): array
    {
        if ($depth >= 4) {
            return ['truncated' => true];
        }

        $sanitized = [];

        foreach (array_slice($metadata, 0, 25, true) as $key => $value) {
            if ($this->shouldRedactKey((string) $key)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[(string) $key] = $this->sanitizeMetadata($value, $depth + 1);

                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $sanitized[(string) $key] = $value;

                continue;
            }

            $sanitized[(string) $key] = Str::limit(trim((string) $value), 500, '...');
        }

        return $sanitized;
    }

    private function shouldRedactKey(string $key): bool
    {
        return in_array(Str::lower($key), [
            'password',
            'password_confirmation',
            'token',
            'trusted_device_token',
            'remote_session_token',
            'payload',
            'server_snapshot',
            'local_payload',
            'server_payload',
        ], true);
    }
}
