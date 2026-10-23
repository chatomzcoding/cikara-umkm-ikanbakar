<?php

namespace App\Support\Crud;

final readonly class CrudGenerationOptions
{
    /** @param array<int, FieldDefinition> $fields */
    public function __construct(
        public string $name,
        public string $target,
        public ?string $module,
        public array $fields,
        public bool $softDeletes,
        public bool $export,
        public bool $bulkActions,
        public bool $permissions,
        public bool $route,
        public bool $test,
        public bool $audit = true,
        public bool $force = false,
        public bool $import = true,
    ) {}
}
