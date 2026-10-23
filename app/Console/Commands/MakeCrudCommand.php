<?php

namespace App\Console\Commands;

use App\Support\Crud\CrudGenerationOptions;
use App\Support\Crud\CrudGenerator;
use App\Support\Crud\FieldDefinition;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class MakeCrudCommand extends Command
{
    protected $signature = 'make:crud
        {name? : Nama model dalam bentuk tunggal}
        {--app : Buat CRUD pada aplikasi utama}
        {--module= : Nama module tujuan}
        {--fields= : Field, contoh name:string,price:decimal,active:boolean}
        {--soft-delete : Aktifkan soft delete}
        {--no-soft-delete : Nonaktifkan soft delete}
        {--export : Aktifkan export CSV, Excel, dan PDF}
        {--no-export : Nonaktifkan export CSV, Excel, dan PDF}
        {--import : Aktifkan import Excel/CSV}
        {--no-import : Nonaktifkan import Excel/CSV}
        {--bulk : Aktifkan bulk action}
        {--no-bulk : Nonaktifkan bulk action}
        {--permissions : Buat katalog permission}
        {--no-permissions : Jangan buat katalog permission}
        {--route : Buat dan daftarkan route}
        {--no-route : Jangan buat route}
        {--test : Buat test Pest}
        {--no-test : Jangan buat test Pest}
        {--audit : Aktifkan audit log model}
        {--no-audit : Nonaktifkan audit log model}
        {--force : Timpa file CRUD yang sudah tersedia}';

    protected $description = 'Membuat CRUD Livewire reusable secara interaktif untuk aplikasi atau module';

    public function handle(CrudGenerator $generator): int
    {
        try {
            $options = $this->collectOptions();
            $conflicts = $generator->conflicts($options, base_path());

            if ($conflicts !== [] && ! $options->force) {
                $this->warn('Beberapa file target sudah tersedia:');
                foreach ($conflicts as $path) {
                    $this->line(' - '.Str::after($path, base_path().DIRECTORY_SEPARATOR));
                }

                if (! $this->input->isInteractive() || ! $this->confirm('Timpa seluruh file tersebut?', false)) {
                    $this->components->error('Pembuatan CRUD dibatalkan. Gunakan --force untuk menimpa file.');

                    return self::FAILURE;
                }

                $options = new CrudGenerationOptions(...[...get_object_vars($options), 'force' => true]);
            }

            $written = $generator->generate($options, base_path());
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('CRUD berhasil dibuat.');
        foreach ($written as $path) {
            $this->line(' <fg=gray>'.Str::after($path, base_path().DIRECTORY_SEPARATOR).'</>');
        }

        $this->newLine();
        $this->components->bulletList([
            'Periksa field, validation rule, dan policy yang dihasilkan.',
            'Jalankan php artisan migrate.',
            ...($options->permissions ? ['Jalankan php artisan db:seed --class=PermissionSeeder.'] : []),
        ]);

        return self::SUCCESS;
    }

    private function collectOptions(): CrudGenerationOptions
    {
        $interactive = $this->input->isInteractive();
        $name = $this->argument('name') ?: ($interactive ? $this->ask('Nama model (tunggal)') : null);

        if (! is_string($name) || ! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException('Nama model wajib diisi dan hanya boleh berisi huruf, angka, atau underscore.');
        }

        [$target, $module] = $this->resolveTarget($interactive);
        $fieldInput = $this->option('fields') ?: ($interactive
            ? $this->ask('Field (format nama:tipe[:nullable][:unique], pisahkan koma)', 'name:string')
            : 'name:string');

        return new CrudGenerationOptions(
            name: Str::studly($name),
            target: $target,
            module: $module,
            fields: FieldDefinition::parseList((string) $fieldInput),
            softDeletes: $this->featureChoice('soft-delete', 'Gunakan soft delete dan menu Sampah?', true, $interactive),
            export: $this->featureChoice('export', 'Aktifkan export CSV, Excel, dan PDF?', true, $interactive),
            bulkActions: $this->featureChoice('bulk', 'Aktifkan checkbox dan bulk action?', true, $interactive),
            permissions: $this->featureChoice('permissions', 'Buat permission dan policy granular?', true, $interactive),
            route: $this->featureChoice('route', 'Buat dan daftarkan route?', true, $interactive),
            test: $this->featureChoice('test', 'Buat test Pest?', true, $interactive),
            audit: $this->featureChoice('audit', 'Aktifkan audit log untuk CRUD ini?', true, $interactive),
            force: (bool) $this->option('force'),
            import: $this->featureChoice('import', 'Aktifkan import Excel/CSV?', true, $interactive),
        );
    }

    /** @return array{string, ?string} */
    private function resolveTarget(bool $interactive): array
    {
        if ($this->option('app') && $this->option('module')) {
            throw new InvalidArgumentException('Gunakan salah satu dari --app atau --module.');
        }

        if ($module = $this->option('module')) {
            $module = Str::studly((string) $module);
            $this->ensureModuleExists($module);

            return ['module', $module];
        }

        if ($this->option('app') || ! $interactive) {
            return ['app', null];
        }

        $target = $this->choice('Lokasi CRUD', ['Aplikasi utama', 'Module'], 1);
        if ($target === 'Aplikasi utama') {
            return ['app', null];
        }

        $modules = collect(glob(base_path('Modules/*/module.json')) ?: [])
            ->map(fn (string $path) => basename(dirname($path)))
            ->sort()
            ->values()
            ->all();

        if ($modules === []) {
            throw new InvalidArgumentException('Belum ada module. Buat module terlebih dahulu dengan module:make.');
        }

        $module = $this->choice('Pilih module tujuan', array_combine($modules, $modules));

        return ['module', $module];
    }

    private function featureChoice(string $option, string $question, bool $default, bool $interactive): bool
    {
        if ($this->option($option) && $this->option('no-'.$option)) {
            throw new InvalidArgumentException("Opsi --{$option} tidak dapat digunakan bersama --no-{$option}.");
        }

        if ($this->option($option)) {
            return true;
        }

        if ($this->option('no-'.$option)) {
            return false;
        }

        return $interactive ? $this->confirm($question, $default) : $default;
    }

    private function ensureModuleExists(string $module): void
    {
        if (! is_file(base_path("Modules/{$module}/module.json"))) {
            throw new InvalidArgumentException("Module [{$module}] tidak ditemukan.");
        }
    }
}
