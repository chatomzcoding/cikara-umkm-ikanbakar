<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditLogger
{
    private ?string $batchUuid = null;

    private ?User $actorOverride = null;

    public function record(
        string $event,
        string $description,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        ?User $actor = null,
    ): ?AuditLog {
        if (! config('audit.enabled', true) || ! Schema::hasTable('audit_logs')) {
            return null;
        }

        $actor ??= $this->actorOverride ?? Auth::user();
        $request = app()->bound('request') ? request() : null;

        return AuditLog::query()->create([
            'user_id' => $actor?->getKey(),
            'user_name' => $actor?->name,
            'user_email' => $actor?->email,
            'event' => $event,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'description' => $description,
            'old_values' => $this->sanitize($oldValues) ?: null,
            'new_values' => $this->sanitize($newValues) ?: null,
            'metadata' => $this->sanitize($metadata) ?: null,
            'batch_uuid' => $this->batchUuid,
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 1000, ''),
            'url' => $request?->fullUrl(),
            'request_method' => $request?->method(),
        ]);
    }

    public function runInBatch(callable $callback): mixed
    {
        $previous = $this->batchUuid;
        $this->batchUuid = (string) Str::uuid();

        try {
            return $callback($this->batchUuid);
        } finally {
            $this->batchUuid = $previous;
        }
    }

    public function runAs(User $actor, callable $callback): mixed
    {
        $previous = $this->actorOverride;
        $this->actorOverride = $actor;

        try {
            return $callback();
        } finally {
            $this->actorOverride = $previous;
        }
    }

    public function sanitize(array $values): array
    {
        $sensitive = array_map('strtolower', config('audit.sensitive_fields', []));

        return collect($values)->map(function (mixed $value, string|int $key) use ($sensitive): mixed {
            $normalized = strtolower((string) $key);
            if (in_array($normalized, $sensitive, true) || Str::contains($normalized, ['password', 'secret', 'token', 'recovery'])) {
                return '[REDACTED]';
            }

            return is_array($value) ? $this->sanitize($value) : $value;
        })->all();
    }
}
