<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => $model->writeAudit('created', [], $model->getAttributes()));

        static::updated(function (Model $model): void {
            $changes = collect($model->getChanges())->except('updated_at')->all();
            if ($changes === []) {
                return;
            }

            $old = collect(array_keys($changes))->mapWithKeys(
                fn (string $key) => [$key => $model->getRawOriginal($key)],
            )->all();
            $model->writeAudit('updated', $old, $changes);
        });

        static::deleted(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            $event = method_exists($model, 'trashed') && $model->trashed() ? 'soft_deleted' : 'deleted';
            $model->writeAudit($event, $model->getAttributes());
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $model) => $model->writeAudit('restored', [], $model->getAttributes()));
        }

        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(fn (Model $model) => $model->writeAudit('force_deleted', $model->getAttributes()));
        }
    }

    protected function writeAudit(string $event, array $oldValues = [], array $newValues = []): void
    {
        app(AuditLogger::class)->record(
            event: $event,
            description: sprintf('%s %s', class_basename($this), str_replace('_', ' ', $event)),
            subject: $this,
            oldValues: $oldValues,
            newValues: $newValues,
        );
    }
}
