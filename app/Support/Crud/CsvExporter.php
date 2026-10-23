<?php

namespace App\Support\Crud;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * @param  array<string, string|Closure(Model): mixed>  $columns
     */
    public function download(Builder $query, array $columns, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $columns): void {
            $output = fopen('php://output', 'wb');

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_keys($columns));

            $query->chunkById(500, function ($records) use ($columns, $output): void {
                foreach ($records as $record) {
                    $row = collect($columns)
                        ->map(fn (string|Closure $resolver) => $this->resolveValue($record, $resolver))
                        ->all();

                    fputcsv($output, $row);
                }
            });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveValue(Model $record, string|Closure $resolver): string
    {
        $value = ExportValue::format($record, $resolver);

        // Prevent spreadsheet applications from evaluating exported values as formulas.
        if (preg_match('/^[=+\-@\t\r]/u', $value) === 1) {
            $value = "'{$value}";
        }

        return $value;
    }
}
