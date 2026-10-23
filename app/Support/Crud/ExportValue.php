<?php

namespace App\Support\Crud;

use BackedEnum;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Mengubah nilai kolom export menjadi string yang siap ditulis ke CSV maupun PDF.
 */
final class ExportValue
{
    /**
     * @param  string|Closure(Model): mixed  $resolver
     */
    public static function format(Model $record, string|Closure $resolver, string $dateFormat = 'Y-m-d H:i:s'): string
    {
        $value = $resolver instanceof Closure
            ? $resolver($record)
            : data_get($record, $resolver);

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            $value = $value->format($dateFormat);
        }

        if (is_bool($value)) {
            $value = $value ? 'Ya' : 'Tidak';
        }

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) ($value ?? '');
    }
}
