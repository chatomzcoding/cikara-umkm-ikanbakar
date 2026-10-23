<?php

namespace App\Support\Crud;

final readonly class ImportResult
{
    /** @param list<string> $errors */
    public function __construct(
        public int $imported,
        public array $errors = [],
    ) {}

    public function failed(): bool
    {
        return $this->errors !== [];
    }
}
