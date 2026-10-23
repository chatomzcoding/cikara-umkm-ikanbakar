<?php

namespace App\Support\Crud;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

final class CrudGenerator
{
    public function __construct(private readonly Filesystem $files) {}

    /** @return array<int, string> */
    public function conflicts(CrudGenerationOptions $options, string $basePath): array
    {
        return array_values(array_filter(
            array_keys($this->fileMap($options, $basePath)),
            fn (string $path) => $this->files->exists($path),
        ));
    }

    /** @return array<int, string> */
    public function generate(CrudGenerationOptions $options, string $basePath): array
    {
        $conflicts = $this->conflicts($options, $basePath);

        if ($conflicts !== [] && ! $options->force) {
            throw new RuntimeException("File target sudah tersedia:\n- ".implode("\n- ", $conflicts));
        }

        $tokens = $this->tokens($options);
        $written = [];

        foreach ($this->fileMap($options, $basePath) as $path => $stub) {
            $this->files->ensureDirectoryExists(dirname($path));
            $contents = $this->files->get($basePath.'/stubs/crud/'.$stub);
            $this->files->put($path, str_replace(array_keys($tokens), array_values($tokens), $contents));
            $written[] = $path;
        }

        if ($options->route) {
            $this->ensureRouteLoader($options, $basePath);
        }

        return $written;
    }

    /** @return array<string, string> */
    private function fileMap(CrudGenerationOptions $options, string $basePath): array
    {
        $name = Str::studly($options->name);
        $resource = Str::kebab(Str::pluralStudly($name));
        $timestamp = now()->format('Y_m_d_His');

        if ($options->target === 'module') {
            $module = Str::studly((string) $options->module);
            $moduleAlias = Str::kebab($module);
            $root = $basePath.'/Modules/'.$module;
            $files = [
                "{$root}/app/Models/{$name}.php" => 'model.stub',
                "{$root}/app/Policies/{$name}Policy.php" => 'policy.stub',
                "{$root}/app/Http/Controllers/{$name}Controller.php" => 'controller.stub',
                "{$root}/database/migrations/{$timestamp}_create_".Str::snake(Str::pluralStudly($name)).'_table.php' => 'migration.stub',
                "{$root}/resources/views/{$resource}/index.blade.php" => 'page.stub',
                "{$basePath}/resources/views/components/{$moduleAlias}/{$resource}/⚡index.blade.php" => 'index.stub',
                "{$basePath}/resources/views/components/{$moduleAlias}/{$resource}/⚡form.blade.php" => 'form.stub',
            ];

            if ($options->route) {
                $files["{$root}/routes/crud/{$resource}.php"] = 'route.stub';
            }
            if ($options->permissions) {
                $files["{$root}/config/permissions/{$resource}.php"] = 'permissions.stub';
            }
            if ($options->test) {
                $files["{$root}/tests/Feature/{$name}ManagementTest.php"] = 'test.stub';
            }

            return $files;
        }

        $files = [
            "{$basePath}/app/Models/{$name}.php" => 'model.stub',
            "{$basePath}/app/Policies/{$name}Policy.php" => 'policy.stub',
            "{$basePath}/app/Http/Controllers/{$name}Controller.php" => 'controller.stub',
            "{$basePath}/database/migrations/{$timestamp}_create_".Str::snake(Str::pluralStudly($name)).'_table.php' => 'migration.stub',
            "{$basePath}/resources/views/crud/{$resource}/index.blade.php" => 'page.stub',
            "{$basePath}/resources/views/components/app/{$resource}/⚡index.blade.php" => 'index.stub',
            "{$basePath}/resources/views/components/app/{$resource}/⚡form.blade.php" => 'form.stub',
        ];

        if ($options->route) {
            $files["{$basePath}/routes/crud/{$resource}.php"] = 'route.stub';
        }
        if ($options->permissions) {
            $files["{$basePath}/config/permissions/{$resource}.php"] = 'permissions.stub';
        }
        if ($options->test) {
            $files["{$basePath}/tests/Feature/Generated/{$name}ManagementTest.php"] = 'test.stub';
        }

        return $files;
    }

    /** @return array<string, string> */
    private function tokens(CrudGenerationOptions $options): array
    {
        $name = Str::studly($options->name);
        $plural = Str::pluralStudly($name);
        $resource = Str::kebab($plural);
        $table = Str::snake($plural);
        $module = Str::studly((string) $options->module);
        $moduleAlias = Str::kebab($module);
        $modelNamespace = $options->target === 'module' ? "Modules\\{$module}\\Models" : 'App\\Models';
        $policyNamespace = $options->target === 'module' ? "Modules\\{$module}\\Policies" : 'App\\Policies';
        $controllerNamespace = $options->target === 'module' ? "Modules\\{$module}\\Http\\Controllers" : 'App\\Http\\Controllers';
        $componentName = $options->target === 'module' ? "{$moduleAlias}.{$resource}" : "app.{$resource}";
        $permission = $options->target === 'module' ? "{$moduleAlias}.{$resource}" : $resource;
        $searchFields = array_values(array_filter($options->fields, fn (FieldDefinition $field) => in_array($field->type, ['string', 'text'], true)));
        $sortField = ($searchFields[0] ?? $options->fields[0])->name;

        return [
            '%%MODEL_NAMESPACE%%' => $modelNamespace,
            '%%POLICY_NAMESPACE%%' => $policyNamespace,
            '%%CONTROLLER_NAMESPACE%%' => $controllerNamespace,
            '%%MODEL_CLASS%%' => $name,
            '%%MODEL_VARIABLE%%' => Str::camel($name),
            '%%MODEL_LABEL%%' => Str::headline($name),
            '%%PLURAL_LABEL%%' => Str::headline($plural),
            '%%TABLE%%' => $table,
            '%%RESOURCE%%' => $resource,
            '%%MODULE_ALIAS%%' => $moduleAlias,
            '%%COMPONENT_NAME%%' => $componentName,
            '%%VIEW_NAME%%' => $options->target === 'module' ? "{$moduleAlias}::{$resource}.index" : "crud.{$resource}.index",
            '%%ROUTE_NAME%%' => $options->target === 'module' ? "{$moduleAlias}.{$resource}.index" : "{$resource}.index",
            '%%ROUTE_URI%%' => $options->target === 'module' ? "{$moduleAlias}/{$resource}" : $resource,
            '%%PERMISSION%%' => $permission,
            '%%FILLABLE%%' => implode("\n", array_map(fn (FieldDefinition $field) => "        '{$field->name}',", $options->fields)),
            '%%CASTS%%' => implode("\n", array_filter(array_map(fn (FieldDefinition $field) => $field->castLine(), $options->fields))),
            '%%SOFT_DELETE_IMPORT%%' => $options->softDeletes ? "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n" : '',
            '%%SOFT_DELETE_TRAIT%%' => $options->softDeletes ? "    use SoftDeletes;\n" : '',
            '%%AUDIT_IMPORT%%' => $options->audit ? "use App\\Models\\Concerns\\Auditable;\n" : '',
            '%%AUDIT_TRAIT%%' => $options->audit ? "    use Auditable;\n" : '',
            '%%ULID_IMPORT%%' => "use App\\Models\\Concerns\\HasPublicUlid;\n",
            '%%ULID_TRAIT%%' => "    use HasPublicUlid;\n",
            '%%MIGRATION_FIELDS%%' => implode("\n", array_map(fn (FieldDefinition $field) => $field->migrationLine(), $options->fields)),
            '%%SOFT_DELETE_MIGRATION%%' => $options->softDeletes ? "\n            \$table->softDeletes();" : '',
            '%%SEARCH_BODY%%' => $this->searchBody($searchFields),
            '%%POLICY_BODY%%' => $this->policyBody($options, $name, $permission),
            '%%SORT_FIELD%%' => $sortField,
            '%%SORTABLE_FIELDS%%' => implode("', '", array_map(fn (FieldDefinition $field) => $field->name, $options->fields)),
            '%%EXPORT_COLUMNS%%' => implode("\n", array_map(fn (FieldDefinition $field) => "            '".Str::headline($field->name)."' => '{$field->name}',", $options->fields)),
            '%%QUERY_TRASH%%' => $options->softDeletes ? '$this->applyTrashMode($query)' : '$query',
            '%%TOOLBAR_EXPORT%%' => $options->export ? ' :show-export="true" :show-excel="true" :show-pdf="true"'.($options->permissions ? " export-permission=\"{$permission}.export\"" : '') : '',
            '%%SHOW_TRASH%%' => $options->softDeletes ? 'true' : 'false',
            '%%IMPORT_USE%%' => $options->import ? "use App\\Livewire\\Concerns\\WithCrudImport;\n" : '',
            '%%IMPORT_TRAIT%%' => $options->import ? "    use WithCrudImport;\n" : '',
            '%%IMPORT_METHODS%%' => $this->importMethods($options, $permission, $table),
            '%%TOOLBAR_IMPORT%%' => $options->import
                ? ' :show-import="true"'.($options->permissions ? " import-permission=\"{$permission}.import\"" : '').' :import-errors="$importErrors"'
                : '',
            '%%USES_SOFT_DELETES%%' => $options->softDeletes ? 'true' : 'false',
            '%%SELECTION_HEADER%%' => $options->bulkActions ? '@if ($trashMode !== \'all\') <x-crud.select-page :ids="$this->items->pluck(\'id\')" :selected="$selected" /> @endif' : '',
            '%%SELECTION_CELL%%' => $options->bulkActions ? '@if ($trashMode !== \'all\') <x-crud.row-checkbox :id="$item->id" /> @endif' : '',
            '%%BULK_ACTIONS%%' => $this->bulkActionsMarkup($options, $permission),
            '%%TABLE_HEADERS%%' => $this->tableHeaders($options->fields),
            '%%TABLE_CELLS%%' => $this->tableCells($options->fields),
            '%%TRASH_STATUS_HEADER%%' => $options->softDeletes ? '<th class="bg-slate-200 px-4 py-3 text-center font-semibold uppercase dark:bg-navy-800">Status</th>' : '',
            '%%TRASH_STATUS_CELL%%' => $options->softDeletes ? '<td class="px-4 py-3 text-center"><span class="badge {{ $item->trashed() ? \'bg-error/10 text-error\' : \'bg-success/10 text-success\' }}">{{ $item->trashed() ? \'Sampah\' : \'Aktif\' }}</span></td>' : '',
            '%%ROW_ACTIONS%%' => $this->rowActions($options),
            '%%COLSPAN%%' => (string) (count($options->fields) + ($options->softDeletes ? 1 : 0) + 2),
            '%%FORM_DEFAULTS%%' => implode("\n", array_map(fn (FieldDefinition $field) => "        '{$field->name}' => ".($field->type === 'boolean' ? 'false' : 'null').',', $options->fields)),
            '%%FORM_RULES%%' => implode("\n", array_map(fn (FieldDefinition $field) => $this->formRuleLine($field, $table), $options->fields)),
            '%%FORM_ASSIGNMENTS%%' => implode("\n", array_map(fn (FieldDefinition $field) => "            '{$field->name}' => \$data->{$field->name},", $options->fields)),
            '%%FORM_FIELDS%%' => $this->formFields($options->fields),
            '%%PERMISSION_MIDDLEWARE%%' => $options->permissions ? "->middleware('can:{$permission}.view')" : '',
            '%%PERMISSIONS_ARRAY%%' => $this->permissionsArray($options, $permission),
            '%%TEST_VALUES%%' => implode("\n", array_map(fn (FieldDefinition $field) => "        '{$field->name}' => ".$this->sampleValue($field).',', $options->fields)),
            '%%SOFT_DELETE_TEST%%' => $options->softDeletes ? "\n    \$model->delete();\n    expect({$name}::withTrashed()->find(\$model->getKey()))->not->toBeNull();" : '',
        ];
    }

    /** @param array<int, FieldDefinition> $fields */
    private function searchBody(array $fields): string
    {
        if ($fields === []) {
            return '        return $query;';
        }

        $conditions = array_map(
            fn (FieldDefinition $field, int $index) => ($index === 0 ? '                $query->where' : '                    ->orWhere')."('{$field->name}', 'like', \$term)",
            $fields,
            array_keys($fields),
        );

        return "        return \$query->when(\$search, function (\$query, \$search): void {\n            \$term = '%'.\$search.'%';\n            \$query->where(function (\$query) use (\$term): void {\n".implode("\n", $conditions).";\n            });\n        });";
    }

    private function formRuleLine(FieldDefinition $field, string $table): string
    {
        $rules = "'{$field->validationRules()}'";
        if ($field->unique) {
            $rules .= ", Rule::unique('{$table}', '{$field->name}')->ignore(\$this->id)";
        }

        return "            'form.{$field->name}' => [{$rules}],";
    }

    private function policyBody(CrudGenerationOptions $options, string $name, string $permission): string
    {
        $allow = fn (string $ability) => $options->permissions ? "\$this->allowed(\$user, '{$permission}.{$ability}')" : 'true';
        $viewAllowed = $allow('view');
        $body = "    public function viewAny(User \$user): bool\n    {\n        return {$viewAllowed};\n    }\n\n";
        $body .= "    public function view(User \$user, {$name} \$model): bool\n    {\n        return {$viewAllowed};\n    }\n\n";
        foreach (['create', 'update', 'delete'] as $ability) {
            $modelArgument = $ability === 'create' ? '' : ", {$name} \$model";
            $allowed = $allow($ability);
            $body .= "    public function {$ability}(User \$user{$modelArgument}): bool\n    {\n        return {$allowed};\n    }\n\n";
        }
        if ($options->softDeletes) {
            foreach (['restore' => 'restore', 'forceDelete' => 'force-delete'] as $method => $ability) {
                $allowed = $allow($ability);
                $body .= "    public function {$method}(User \$user, {$name} \$model): bool\n    {\n        return {$allowed};\n    }\n\n";
            }
        }

        return rtrim($body);
    }

    /** @param array<int, FieldDefinition> $fields */
    private function tableHeaders(array $fields): string
    {
        return implode("\n", array_map(fn (FieldDefinition $field) => "                                    <x-crud.sortable-header field=\"{$field->name}\" label=\"".Str::headline($field->name).'" :sort-field="$sortField" :sort-direction="$sortDirection" />', $fields));
    }

    /** @param array<int, FieldDefinition> $fields */
    private function tableCells(array $fields): string
    {
        return implode("\n", array_map(fn (FieldDefinition $field) => "                                        <td class=\"px-4 py-3\">{{ \$item->{$field->name} }}</td>", $fields));
    }

    private function bulkActionsMarkup(CrudGenerationOptions $options, string $permission): string
    {
        if (! $options->bulkActions) {
            return '';
        }

        $delete = $options->permissions ? " delete-permission=\"{$permission}.delete\"" : '';
        $restore = $options->softDeletes && $options->permissions ? " restore-permission=\"{$permission}.restore\" force-delete-permission=\"{$permission}.force-delete\"" : '';

        return "                    <x-crud.bulk-actions :selected=\"\$selected\" :trash-mode=\"\$trashMode\"{$delete}{$restore} />";
    }

    private function rowActions(CrudGenerationOptions $options): string
    {
        $active = <<<'BLADE'
                                                @can('update', $item)
                                                    <button type="button" @click="openForm = true" wire:click="buttonEdit({{ $item->id }})" class="btn h-8 gap-1.5 bg-primary px-3 text-white"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>Edit</button>
                                                @endcan
                                                @can('delete', $item)
                                                    <button type="button" wire:click="$dispatch('swal-delete', { id: {{ $item->id }}, name: 'crud-delete-item', title: 'Hapus data?', text: 'Lanjutkan penghapusan data ini?' })" class="btn h-8 gap-1.5 bg-error px-3 text-white"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/></svg>Hapus</button>
                                                @endcan
BLADE;
        if (! $options->softDeletes) {
            return $active;
        }

        return <<<'BLADE'
                                            @if ($item->trashed())
                                                @can('restore', $item)
                                                    <button type="button" wire:click="restoreItem({{ $item->id }})" class="btn h-8 gap-1.5 bg-success px-3 text-white"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></svg>Pulihkan</button>
                                                @endcan
                                                @can('forceDelete', $item)
                                                    <button type="button" wire:click="$dispatch('swal-delete', { id: {{ $item->id }}, name: 'crud-force-delete-item', title: 'Hapus permanen?', text: 'Tindakan ini tidak dapat dibatalkan.' })" class="btn h-8 gap-1.5 bg-error px-3 text-white"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11l4 5M14 11l-4 5"/></svg>Hapus Permanen</button>
                                                @endcan
                                            @else
BLADE."\n{$active}\n                                            @endif";
    }

    /** @param array<int, FieldDefinition> $fields */
    private function formFields(array $fields): string
    {
        return implode("\n", array_map(function (FieldDefinition $field): string {
            $label = Str::headline($field->name);
            if ($field->type === 'boolean') {
                return "        <label class=\"block\"><span>{$label}</span><select wire:model=\"form.{$field->name}\" class=\"form-select mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2\"><option value=\"1\">Ya</option><option value=\"0\">Tidak</option></select>@error('form.{$field->name}') <span class=\"text-xs text-error\">{{ \$message }}</span> @enderror</label>";
            }
            if ($field->type === 'text') {
                return "        <label class=\"block\"><span>{$label}</span><textarea wire:model=\"form.{$field->name}\" class=\"form-textarea mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2\"></textarea>@error('form.{$field->name}') <span class=\"text-xs text-error\">{{ \$message }}</span> @enderror</label>";
            }

            $step = $field->type === 'decimal' ? ' step="0.01"' : '';

            return "        <label class=\"block\"><span>{$label}</span><input type=\"{$field->inputType()}\"{$step} wire:model=\"form.{$field->name}\" class=\"form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2\">@error('form.{$field->name}') <span class=\"text-xs text-error\">{{ \$message }}</span> @enderror</label>";
        }, $fields));
    }

    private function permissionsArray(CrudGenerationOptions $options, string $permission): string
    {
        $abilities = ['view' => 'Melihat', 'create' => 'Menambah', 'update' => 'Mengubah', 'delete' => 'Menghapus'];
        if ($options->export) {
            $abilities['export'] = 'Mengekspor';
        }
        if ($options->import) {
            $abilities['import'] = 'Mengimpor';
        }
        if ($options->softDeletes) {
            $abilities['restore'] = 'Memulihkan';
            $abilities['force-delete'] = 'Menghapus permanen';
        }

        return implode("\n", array_map(fn (string $label, string $ability) => "            '{$permission}.{$ability}' => '{$label} data',", $abilities, array_keys($abilities)));
    }

    private function importMethods(CrudGenerationOptions $options, string $permission, string $table): string
    {
        if (! $options->import) {
            return '';
        }

        $rules = implode("\n", array_map(function (FieldDefinition $field) use ($table): string {
            $unique = $field->unique ? ", 'unique:{$table},{$field->name}'" : '';

            return "            '{$field->name}' => ['{$field->validationRules()}'{$unique}],";
        }, $options->fields));

        $permissionMethod = $options->permissions
            ? "    protected function crudImportPermission(): string\n    {\n        return '{$permission}.import';\n    }\n\n"
            : '';

        return $permissionMethod
            ."    protected function crudImportRules(): array\n    {\n        return [\n{$rules}\n        ];\n    }\n\n";
    }

    private function sampleValue(FieldDefinition $field): string
    {
        return match ($field->type) {
            'integer', 'biginteger' => '10',
            'decimal' => '10.50',
            'boolean' => 'true',
            'date' => "'2026-01-01'",
            'datetime' => "'2026-01-01 10:00:00'",
            default => "'Contoh {$field->name}'",
        };
    }

    private function ensureRouteLoader(CrudGenerationOptions $options, string $basePath): void
    {
        $routeFile = $options->target === 'module'
            ? $basePath.'/Modules/'.Str::studly((string) $options->module).'/routes/web.php'
            : $basePath.'/routes/web.php';
        $marker = '// CRUD-GENERATOR:LOAD-ROUTES';

        if (! $this->files->exists($routeFile)) {
            $this->files->ensureDirectoryExists(dirname($routeFile));
            $this->files->put($routeFile, "<?php\n");
        }

        $contents = $this->files->get($routeFile);
        if (! str_contains($contents, $marker)) {
            $contents = rtrim($contents)."\n\n{$marker}\nforeach (glob(__DIR__.'/crud/*.php') ?: [] as \$crudRouteFile) {\n    require \$crudRouteFile;\n}\n";
            $this->files->put($routeFile, $contents);
        }
    }
}
