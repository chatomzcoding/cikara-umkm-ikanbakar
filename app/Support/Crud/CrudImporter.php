<?php

namespace App\Support\Crud;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

final class CrudImporter
{
    private const MAX_REPORTED_ERRORS = 50;

    public function __construct(private readonly SpreadsheetReader $reader) {}

    /**
     * Semua baris divalidasi lebih dulu; satu baris gagal berarti tidak ada data yang disimpan.
     *
     * @param  array<string, string>  $columns  label header => atribut model
     * @param  array<string, mixed>  $rules  atribut => aturan validasi
     * @param  Closure(array<string, mixed>): mixed  $persist
     */
    public function import(string $path, string $extension, array $columns, array $rules, Closure $persist, int $maxRows): ImportResult
    {
        $rows = array_values(array_filter(
            $this->reader->read($path, $extension),
            fn (array $row) => collect($row)->contains(fn ($value) => trim((string) $value) !== ''),
        ));

        if ($rows === []) {
            return new ImportResult(0, ['File kosong. Isi baris pertama dengan judul kolom dan baris berikutnya dengan data.']);
        }

        $positions = $this->headerPositions(array_shift($rows), $columns);
        $missing = array_keys(array_diff_key($columns, $positions));
        if ($missing !== []) {
            return new ImportResult(0, ['Kolom tidak ditemukan pada baris judul: '.implode(', ', $missing).'. Gunakan template import.']);
        }

        if ($rows === []) {
            return new ImportResult(0, ['File tidak berisi data untuk diimport.']);
        }

        if (count($rows) > $maxRows) {
            return new ImportResult(0, ['Jumlah data '.count($rows)." baris melebihi batas {$maxRows} baris per import."]);
        }

        $labels = array_flip($columns);
        $records = [];
        $errors = [];
        $seen = [];

        foreach ($rows as $offset => $row) {
            $line = $offset + 2;
            $data = [];

            foreach ($columns as $label => $attribute) {
                $data[$attribute] = $this->normalize($row[$positions[$label]] ?? null, $rules[$attribute] ?? []);
            }

            $validator = Validator::make($data, $rules, [], $labels);
            foreach ($validator->errors()->all() as $message) {
                $errors[] = "Baris {$line}: {$message}";
            }

            foreach ($this->uniqueAttributes($rules) as $attribute) {
                $value = Str::lower((string) $data[$attribute]);
                if ($value === '') {
                    continue;
                }

                if (isset($seen[$attribute][$value])) {
                    $errors[] = "Baris {$line}: {$labels[$attribute]} sama dengan baris {$seen[$attribute][$value]}.";
                } else {
                    $seen[$attribute][$value] = $line;
                }
            }

            $records[] = $data;
        }

        if ($errors !== []) {
            $total = count($errors);
            $errors = array_slice($errors, 0, self::MAX_REPORTED_ERRORS);
            if ($total > self::MAX_REPORTED_ERRORS) {
                $errors[] = 'Dan '.($total - self::MAX_REPORTED_ERRORS).' kesalahan lainnya.';
            }

            return new ImportResult(0, $errors);
        }

        DB::transaction(function () use ($records, $persist): void {
            foreach ($records as $record) {
                $persist($record);
            }
        });

        return new ImportResult(count($records));
    }

    /**
     * Header dicocokkan dengan label atau nama atribut, tanpa membedakan huruf besar/kecil.
     *
     * @param  list<string|null>  $header
     * @param  array<string, string>  $columns
     * @return array<string, int>
     */
    private function headerPositions(array $header, array $columns): array
    {
        $normalized = array_map(fn ($value) => Str::lower(trim((string) $value)), $header);
        $positions = [];

        foreach ($columns as $label => $attribute) {
            foreach ([Str::lower($label), Str::lower($attribute)] as $candidate) {
                $index = array_search($candidate, $normalized, true);
                if ($index !== false) {
                    $positions[$label] = $index;
                    break;
                }
            }
        }

        return $positions;
    }

    private function normalize(?string $value, mixed $rules): mixed
    {
        $value = $value === null ? null : trim($value);
        if ($value === null || $value === '') {
            return null;
        }

        $rules = $this->ruleNames($rules);

        if (in_array('boolean', $rules, true)) {
            return match (Str::lower($value)) {
                'ya', 'yes', 'true', 'aktif', '1' => true,
                'tidak', 'no', 'false', 'nonaktif', '0' => false,
                default => $value,
            };
        }

        if (in_array('date', $rules, true) && is_numeric($value)) {
            // Dihitung di UTC: zona lokal (mis. Asia/Jakarta) memakai offset LMT pada tahun 1899 sehingga hasilnya bergeser.
            $date = CarbonImmutable::create(1899, 12, 30, 0, 0, 0, 'UTC')->addSeconds((int) round((float) $value * 86400));

            return fmod((float) $value, 1.0) === 0.0 ? $date->toDateString() : $date->toDateTimeString();
        }

        return $value;
    }

    /** @return list<string> */
    private function uniqueAttributes(array $rules): array
    {
        $attributes = [];

        foreach ($rules as $attribute => $attributeRules) {
            foreach ((array) $attributeRules as $rule) {
                if ($rule instanceof Unique || (is_string($rule) && str_starts_with($rule, 'unique:'))) {
                    $attributes[] = $attribute;
                    break;
                }
            }
        }

        return $attributes;
    }

    /** @return list<string> */
    private function ruleNames(mixed $rules): array
    {
        $rules = is_string($rules) ? explode('|', $rules) : (array) $rules;

        return array_values(array_filter(array_map(
            fn ($rule) => is_string($rule) ? Str::before($rule, ':') : null,
            $rules,
        )));
    }
}
