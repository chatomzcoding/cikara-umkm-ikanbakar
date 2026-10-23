<?php

namespace App\Livewire\Concerns;

use App\Services\AuditLogger;
use App\Support\Crud\CrudImporter;
use App\Support\Crud\ImportResult;
use App\Support\Crud\XlsxExporter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Dipakai bersama WithCrudTable. Wajib mengimplementasikan crudImportRules().
 */
trait WithCrudImport
{
    use WithFileUploads;

    public $importFile = null;

    /** @var list<string> */
    public array $importErrors = [];

    /**
     * Aturan validasi per baris, kunci = nama atribut model.
     *
     * @return array<string, mixed>
     */
    abstract protected function crudImportRules(): array;

    /** Kosong = cukup Gate `create` pada model. */
    protected function crudImportPermission(): string
    {
        return '';
    }

    /**
     * Label header => atribut. Default: kolom export yang berupa atribut dan memiliki aturan import.
     *
     * @return array<string, string>
     */
    protected function crudImportColumns(): array
    {
        $rules = $this->crudImportRules();

        return array_filter(
            $this->crudExportColumns(),
            fn ($attribute) => is_string($attribute) && array_key_exists($attribute, $rules),
        );
    }

    protected function crudImportMaxRows(): int
    {
        return 1000;
    }

    /** @param array<string, mixed> $data */
    protected function crudImportRow(array $data): void
    {
        $this->crudModelClass()::create($data);
    }

    public function importExcel(): void
    {
        $this->authorizeCrudImport();
        $this->importErrors = [];

        $validator = Validator::make(
            ['importFile' => $this->importFile],
            ['importFile' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120']],
            [
                'importFile.required' => 'Pilih file yang akan diimport.',
                'importFile.mimes' => 'File harus berformat .xlsx atau .csv.',
                'importFile.max' => 'Ukuran file maksimal 5 MB.',
            ],
        );

        if ($validator->fails()) {
            $this->importErrors = $validator->errors()->all();

            return;
        }

        $extension = Str::lower($this->importFile->getClientOriginalExtension());

        try {
            $result = app(AuditLogger::class)->runInBatch(fn () => app(CrudImporter::class)->import(
                $this->importFile->getRealPath(),
                $extension,
                $this->crudImportColumns(),
                $this->crudImportRules(),
                fn (array $data) => $this->crudImportRow($data),
                $this->crudImportMaxRows(),
            ));
        } catch (Throwable $exception) {
            report($exception);
            $result = new ImportResult(0, ['File tidak dapat diproses: '.$exception->getMessage()]);
        }

        if ($result->failed()) {
            $this->importErrors = $result->errors;
            $this->crudNotify('Import dibatalkan, tidak ada data yang disimpan.', 'error');

            return;
        }

        app(AuditLogger::class)->record(
            event: 'imported',
            description: 'Import '.class_basename($this->crudModelClass()),
            metadata: [
                'model' => $this->crudModelClass(),
                'rows' => $result->imported,
                'filename' => $this->importFile->getClientOriginalName(),
            ],
        );

        $this->reset('importFile');
        $this->resetPage();
        unset($this->items);
        $this->dispatch('crud-import-finished');
        $this->crudNotify("{$result->imported} data berhasil diimport.");
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        $this->authorizeCrudImport();

        $modelClass = $this->crudModelClass();
        $name = Str::of(class_basename($modelClass))->snake('-');

        return app(XlsxExporter::class)->download(
            $modelClass::query()->whereRaw('1 = 0'),
            $this->crudImportColumns(),
            "template-import-{$name}.xlsx",
        );
    }

    public function clearImport(): void
    {
        $this->reset('importFile', 'importErrors');
    }

    private function authorizeCrudImport(): void
    {
        $permission = $this->crudImportPermission();

        $permission !== ''
            ? Gate::authorize($permission)
            : Gate::authorize('create', $this->crudModelClass());
    }
}
