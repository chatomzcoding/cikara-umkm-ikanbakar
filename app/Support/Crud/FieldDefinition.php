<?php

namespace App\Support\Crud;

use InvalidArgumentException;

final class FieldDefinition
{
    private const TYPES = ['string', 'text', 'integer', 'biginteger', 'boolean', 'date', 'datetime', 'decimal'];

    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $nullable = false,
        public readonly bool $unique = false,
    ) {}

    /** @return array<int, self> */
    public static function parseList(string $definition): array
    {
        $fields = array_filter(array_map('trim', explode(',', $definition)));

        if ($fields === []) {
            throw new InvalidArgumentException('Minimal satu field harus diberikan.');
        }

        $parsed = array_map(self::parse(...), $fields);
        $names = array_column($parsed, 'name');

        if (count($names) !== count(array_unique($names))) {
            throw new InvalidArgumentException('Nama field tidak boleh duplikat.');
        }

        return $parsed;
    }

    public static function parse(string $definition): self
    {
        $segments = array_map('trim', explode(':', $definition));
        $name = array_shift($segments);
        $type = strtolower(array_shift($segments) ?: 'string');

        if (! preg_match('/^[a-z][a-z0-9_]*$/', (string) $name)) {
            throw new InvalidArgumentException("Nama field [{$name}] tidak valid.");
        }

        if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
            throw new InvalidArgumentException("Field [{$name}] dibuat otomatis dan tidak perlu ditulis.");
        }

        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("Tipe [{$type}] tidak didukung.");
        }

        $modifiers = array_map('strtolower', $segments);
        $unknown = array_diff($modifiers, ['nullable', 'unique']);

        if ($unknown !== []) {
            throw new InvalidArgumentException('Modifier tidak didukung: '.implode(', ', $unknown));
        }

        return new self(
            name: $name,
            type: $type,
            nullable: in_array('nullable', $modifiers, true),
            unique: in_array('unique', $modifiers, true),
        );
    }

    public function migrationLine(): string
    {
        $method = match ($this->type) {
            'biginteger' => 'bigInteger',
            'datetime' => 'dateTime',
            default => $this->type,
        };
        $arguments = $this->type === 'decimal' ? "'{$this->name}', 15, 2" : "'{$this->name}'";
        $modifiers = ($this->nullable ? '->nullable()' : '').($this->unique ? '->unique()' : '');

        return "            \$table->{$method}({$arguments}){$modifiers};";
    }

    public function validationRules(): string
    {
        $rules = [$this->nullable ? 'nullable' : 'required'];
        $rules[] = match ($this->type) {
            'integer', 'biginteger' => 'integer',
            'decimal' => 'numeric',
            'boolean' => 'boolean',
            'date', 'datetime' => 'date',
            default => 'string',
        };

        if ($this->type === 'string') {
            $rules[] = 'max:255';
        }

        return implode("', '", $rules);
    }

    public function castLine(): ?string
    {
        $cast = match ($this->type) {
            'integer', 'biginteger' => 'integer',
            'decimal' => 'decimal:2',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
            default => null,
        };

        return $cast ? "            '{$this->name}' => '{$cast}'," : null;
    }

    public function inputType(): string
    {
        return match ($this->type) {
            'integer', 'biginteger', 'decimal' => 'number',
            'date' => 'date',
            'datetime' => 'datetime-local',
            default => 'text',
        };
    }
}
