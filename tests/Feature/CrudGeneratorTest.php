<?php

use App\Support\Crud\CrudGenerationOptions;
use App\Support\Crud\CrudGenerator;
use App\Support\Crud\FieldDefinition;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

test('generator membuat CRUD lengkap pada module tanpa token tersisa', function () {
    $files = app(Filesystem::class);
    $temporaryPath = storage_path('framework/testing/crud-generator-'.Str::uuid());

    try {
        $files->makeDirectory($temporaryPath.'/Modules/Inventory/routes', 0755, true);
        $files->copyDirectory(base_path('stubs'), $temporaryPath.'/stubs');
        $files->put($temporaryPath.'/Modules/Inventory/module.json', '{}');
        $files->put($temporaryPath.'/Modules/Inventory/routes/web.php', "<?php\n");

        $options = new CrudGenerationOptions(
            name: 'Product',
            target: 'module',
            module: 'Inventory',
            fields: FieldDefinition::parseList('name:string:unique,price:decimal,active:boolean'),
            softDeletes: true,
            export: true,
            bulkActions: true,
            permissions: true,
            route: true,
            test: true,
        );

        $written = app(CrudGenerator::class)->generate($options, $temporaryPath);
        $form = $files->get($temporaryPath.'/resources/views/components/inventory/products/⚡form.blade.php');
        $index = $files->get($temporaryPath.'/resources/views/components/inventory/products/⚡index.blade.php');

        expect($written)->toHaveCount(10)
            ->and($files->exists($temporaryPath.'/Modules/Inventory/app/Models/Product.php'))->toBeTrue()
            ->and($files->exists($temporaryPath.'/resources/views/components/inventory/products/⚡index.blade.php'))->toBeTrue()
            ->and($files->get($temporaryPath.'/Modules/Inventory/app/Models/Product.php'))
            ->toContain('namespace Modules\\Inventory\\Models;', 'use SoftDeletes;')
            ->toContain('use App\\Models\\Concerns\\Auditable;', 'use Auditable;')
            ->not->toContain('%%')
            ->and($files->get($temporaryPath.'/Modules/Inventory/routes/web.php'))
            ->toContain('CRUD-GENERATOR:LOAD-ROUTES')
            ->and($files->get($temporaryPath.'/Modules/Inventory/config/permissions/products.php'))
            ->toContain("'inventory.products.export'", "'inventory.products.force-delete'", "'inventory.products.import'")
            ->and($form)->toContain(
                'type="submit"',
                'Simpan',
                'wire:click="buttonBatalkan"',
                'Batalkan',
                'public function buttonReset(): void',
                'private function resetForm(): void',
            )
            ->and($index)->toContain(
                'x-on:handle-form.window="openForm = false"',
                '$this->sortField = $this->crudDefaultSortField();',
                "return 'name';",
                ':show-excel="true"',
                'use WithCrudImport;',
                "return 'inventory.products.import';",
                "'name' => ['required', 'string', 'max:255', 'unique:products,name'],",
                ':show-import="true" import-permission="inventory.products.import" :import-errors="$importErrors"',
            );

        foreach ($written as $path) {
            if (str_ends_with($path, '.php') && ! str_ends_with($path, '.blade.php')) {
                $lint = new Process([PHP_BINARY, '-l', $path]);
                $lint->run();
                expect($lint->isSuccessful())->toBeTrue($lint->getErrorOutput().$lint->getOutput());
            }
        }

        foreach (['⚡index.blade.php', '⚡form.blade.php'] as $view) {
            $source = $files->get($temporaryPath.'/resources/views/components/inventory/products/'.$view);
            $compiledPath = $temporaryPath.'/'.$view.'.php';
            $files->put($compiledPath, Blade::compileString($source));
            $lint = new Process([PHP_BINARY, '-l', $compiledPath]);
            $lint->run();
            expect($lint->isSuccessful())->toBeTrue($lint->getErrorOutput().$lint->getOutput());
        }
    } finally {
        if ($files->isDirectory($temporaryPath)) {
            $files->deleteDirectory($temporaryPath);
        }
    }
});

test('generator dapat mematikan fitur opsional pada target aplikasi', function () {
    $files = app(Filesystem::class);
    $temporaryPath = storage_path('framework/testing/crud-generator-'.Str::uuid());

    try {
        $files->makeDirectory($temporaryPath.'/stubs', 0755, true);
        $files->copyDirectory(base_path('stubs'), $temporaryPath.'/stubs');

        $options = new CrudGenerationOptions(
            name: 'Note',
            target: 'app',
            module: null,
            fields: FieldDefinition::parseList('title:string,content:text:nullable'),
            softDeletes: false,
            export: false,
            bulkActions: false,
            permissions: false,
            route: false,
            test: false,
            audit: false,
            import: false,
        );

        $written = app(CrudGenerator::class)->generate($options, $temporaryPath);
        $index = $files->get($temporaryPath.'/resources/views/components/app/notes/⚡index.blade.php');

        expect($written)->toHaveCount(7)
            ->and($index)->toContain('return false;', ':show-trash="false"')
            ->not->toContain('x-crud.bulk-actions', 'export-permission=', ':show-excel="true"', 'WithCrudImport', 'show-import', '%%');

        expect($files->get($temporaryPath.'/app/Models/Note.php'))->not->toContain('Auditable');
    } finally {
        if ($files->isDirectory($temporaryPath)) {
            $files->deleteDirectory($temporaryPath);
        }
    }
});
