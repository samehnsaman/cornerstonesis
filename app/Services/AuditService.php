<?php

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditService
{
    private const REDACTED_KEYS = ['password', 'token', 'secret', 'authorization', 'government_id'];

    public function record(string $action, Model|string $subject, array $before = [], array $after = [], ?string $reason = null, array $metadata = []): AuditEvent
    {
        $request = request();

        return AuditEvent::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject instanceof Model ? $subject::class : 'system',
            'subject_id' => $subject instanceof Model ? (string) $subject->getKey() : $subject,
            'reason' => $reason,
            'correlation_id' => (string) ($request->header('X-Correlation-ID') ?: Str::uuid()),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000),
            'before' => $this->redact($before),
            'after' => $this->redact($after),
            'metadata' => $this->redact($metadata),
            'occurred_at' => now(),
        ]);
    }

    private function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::REDACTED_KEYS, true)) {
                $values[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }
}
