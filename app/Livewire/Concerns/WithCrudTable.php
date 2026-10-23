<?php

namespace App\Livewire\Concerns;

use App\Services\AuditLogger;
use App\Support\Crud\CsvExporter;
use App\Support\Crud\PdfExporter;
use App\Support\Crud\XlsxExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait WithCrudTable
{
    use WithoutUrlPagination, WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public string $trashMode = 'active';

    /** @var array<int, string> */
    public array $selected = [];

    /** @return class-string<Model> */
    abstract protected function crudModelClass(): string;

    abstract protected function crudFilteredQuery(): Builder;

    /** @return array<int, string> */
    abstract protected function crudSortableFields(): array;

    /** @return array<string, string|\Closure(Model): mixed> */
    abstract protected function crudExportColumns(): array;

    abstract protected function crudExportPermission(): string;

    public function sortBy(string $field): void
    {
        abort_unless(in_array($field, $this->crudSortableFields(), true), 422);

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPageAndSelection();
    }

    /** @param array<int, int|string> $ids */
    public function togglePageSelection(array $ids): void
    {
        $ids = collect($ids)->map(fn ($id) => (string) $id)->unique()->values();
        $selected = collect($this->selected)->map(fn ($id) => (string) $id);

        $this->selected = $ids->diff($selected)->isEmpty()
            ? $selected->diff($ids)->values()->all()
            : $selected->merge($ids)->unique()->values()->all();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function resetCrudTable(): void
    {
        $this->reset(['search', 'perPage', 'trashMode', 'selected']);
        $this->sortField = $this->crudDefaultSortField();
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPageAndSelection();
    }

    public function updatedPerPage(): void
    {
        abort_unless(in_array($this->perPage, [5, 10, 25, 50, 100], true), 422);
        $this->resetPageAndSelection();
    }

    public function updatedTrashMode(): void
    {
        abort_unless(in_array($this->trashMode, ['active', 'trashed', 'all'], true), 422);
        $this->resetPageAndSelection();
    }

    #[On('crud-delete-item')]
    public function deleteItem(int|string $id): void
    {
        $model = $this->findCrudModel($id);
        Gate::authorize('delete', $model);

        if ($reason = $this->crudDeletionBlockReason($model)) {
            $this->crudNotify($reason, 'error');

            return;
        }

        $model->delete();
        $this->clearSelection();
        $message = $this->crudUsesSoftDeletes()
            ? 'Data berhasil dipindahkan ke sampah.'
            : 'Data berhasil dihapus.';
        $this->crudNotify($message, 'deleted');
    }

    public function restoreItem(int|string $id): void
    {
        $model = $this->findCrudModel($id, true);
        Gate::authorize('restore', $model);
        $model->restore();
        $this->clearSelection();
        $this->crudNotify('Data berhasil dipulihkan.');
    }

    #[On('crud-force-delete-item')]
    public function forceDeleteItem(int|string $id): void
    {
        $model = $this->findCrudModel($id, true);
        Gate::authorize('forceDelete', $model);
        $this->crudBeforeForceDelete($model);
        $model->forceDelete();
        $this->clearSelection();
        $this->crudNotify('Data berhasil dihapus permanen.', 'deleted');
    }

    #[On('crud-bulk-delete')]
    public function bulkDelete(): void
    {
        $models = $this->selectedCrudModels();

        foreach ($models as $model) {
            Gate::authorize('delete', $model);

            if ($reason = $this->crudDeletionBlockReason($model)) {
                $this->crudNotify($reason, 'error');

                return;
            }
        }

        app(AuditLogger::class)->runInBatch(fn () => DB::transaction(fn () => $models->each->delete()));
        $count = $models->count();
        $this->clearSelection();
        $message = $this->crudUsesSoftDeletes()
            ? "{$count} data berhasil dipindahkan ke sampah."
            : "{$count} data berhasil dihapus.";
        $this->crudNotify($message, 'deleted');
    }

    public function bulkRestore(): void
    {
        $models = $this->selectedCrudModels(true);

        foreach ($models as $model) {
            Gate::authorize('restore', $model);
        }

        app(AuditLogger::class)->runInBatch(fn () => DB::transaction(fn () => $models->each->restore()));
        $count = $models->count();
        $this->clearSelection();
        $this->crudNotify("{$count} data berhasil dipulihkan.");
    }

    #[On('crud-bulk-force-delete')]
    public function bulkForceDelete(): void
    {
        $models = $this->selectedCrudModels(true);

        foreach ($models as $model) {
            Gate::authorize('forceDelete', $model);
        }

        app(AuditLogger::class)->runInBatch(fn () => DB::transaction(function () use ($models): void {
            foreach ($models as $model) {
                $this->crudBeforeForceDelete($model);
                $model->forceDelete();
            }
        }));

        $count = $models->count();
        $this->clearSelection();
        $this->crudNotify("{$count} data berhasil dihapus permanen.", 'deleted');
    }

    public function exportCsv(): StreamedResponse
    {
        $permission = $this->crudExportPermission();
        Gate::authorize($permission !== '' ? $permission : 'viewAny', $permission !== '' ? [] : $this->crudModelClass());

        $query = $this->crudFilteredQuery();
        if ($this->selected !== []) {
            $query->whereKey($this->selected);
        }

        $name = Str::of(class_basename($this->crudModelClass()))->snake('-');
        $filename = "{$name}-".now()->format('Y-m-d-His').'.csv';
        $this->auditExport('csv', $query);

        return app(CsvExporter::class)->download($query, $this->crudExportColumns(), $filename);
    }

    public function exportExcel(): StreamedResponse
    {
        $permission = $this->crudExcelPermission();
        Gate::authorize($permission !== '' ? $permission : 'viewAny', $permission !== '' ? [] : $this->crudModelClass());

        $query = $this->crudFilteredQuery();
        if ($this->selected !== []) {
            $query->whereKey($this->selected);
        }

        $name = Str::of(class_basename($this->crudModelClass()))->snake('-');
        $filename = "{$name}-".now()->format('Y-m-d-His').'.xlsx';
        $this->auditExport('xlsx', $query);

        return app(XlsxExporter::class)->download($query, $this->crudExcelColumns(), $filename);
    }

    public function exportPdf(): ?StreamedResponse
    {
        $permission = $this->crudPdfPermission();
        Gate::authorize($permission !== '' ? $permission : 'viewAny', $permission !== '' ? [] : $this->crudModelClass());

        $query = $this->crudFilteredQuery();
        if ($this->selected !== []) {
            $query->whereKey($this->selected);
        }

        $total = (clone $query)->count();
        $max = $this->crudPdfMaxRows();

        if ($total > $max) {
            $this->crudNotify("Data terlalu banyak untuk dicetak ({$total} baris, maksimal {$max}). Persempit filter atau gunakan Export CSV/Excel.", 'error');

            return null;
        }

        $name = Str::of(class_basename($this->crudModelClass()))->snake('-');
        $filename = "{$name}-".now()->format('Y-m-d-His').'.pdf';
        $this->auditExport('pdf', $query, $total);

        return app(PdfExporter::class)->download($query, $this->crudPdfColumns(), $filename, [
            'view' => $this->crudPdfView(),
            'title' => $this->crudPdfTitle(),
            'subtitle' => $this->crudPdfSubtitle(),
            'paper' => $this->crudPdfPaper(),
            'orientation' => $this->crudPdfOrientation(),
            'meta' => $this->crudPdfMeta($total),
            'with' => $this->crudPdfData(),
        ]);
    }

    /** Kolom yang dicetak. Default mengikuti kolom export CSV. */
    protected function crudPdfColumns(): array
    {
        return $this->crudExportColumns();
    }

    /** Kolom Excel. Default mengikuti kolom export CSV. */
    protected function crudExcelColumns(): array
    {
        return $this->crudExportColumns();
    }

    protected function crudExcelPermission(): string
    {
        return $this->crudExportPermission();
    }

    protected function auditExport(string $format, Builder $query, ?int $total = null): void
    {
        $model = class_basename($this->crudModelClass());
        app(AuditLogger::class)->record(
            event: 'exported',
            description: "Export {$format} {$model}",
            metadata: [
                'format' => $format,
                'model' => $this->crudModelClass(),
                'rows' => $total ?? (clone $query)->count(),
                'selected_ids' => $this->selected,
                'search' => $this->search,
                'trash_mode' => $this->trashMode,
                'sort' => [$this->sortField, $this->sortDirection],
            ],
        );
    }

    protected function crudPdfPermission(): string
    {
        return $this->crudExportPermission();
    }

    /** Ganti dengan view sendiri bila butuh layout khusus. */
    protected function crudPdfView(): string
    {
        return config('crud.pdf.view') ?: PdfExporter::DEFAULT_VIEW;
    }

    protected function crudPdfTitle(): string
    {
        return 'Daftar '.Str::headline(Str::plural(class_basename($this->crudModelClass())));
    }

    protected function crudPdfSubtitle(): ?string
    {
        return null;
    }

    protected function crudPdfPaper(): string
    {
        return config('crud.pdf.paper', 'a4');
    }

    protected function crudPdfOrientation(): string
    {
        return config('crud.pdf.orientation', 'portrait');
    }

    /** @return array<string, string> Baris keterangan di bawah judul. */
    protected function crudPdfMeta(int $total): array
    {
        $meta = [];

        if ($this->search !== '') {
            $meta['Pencarian'] = $this->search;
        }

        if ($this->crudUsesSoftDeletes()) {
            $meta['Status Data'] = match ($this->trashMode) {
                'trashed' => 'Sampah',
                'all' => 'Semua data',
                default => 'Data aktif',
            };
        }

        if ($this->selected !== []) {
            $meta['Cakupan'] = 'Hanya data yang dipilih';
        }

        $meta['Jumlah Data'] = (string) $total;

        return $meta;
    }

    /** @return array<string, mixed> Data tambahan untuk view PDF kustom. */
    protected function crudPdfData(): array
    {
        return [];
    }

    protected function crudPdfMaxRows(): int
    {
        return (int) config('crud.pdf.max_rows', 2000);
    }

    protected function crudDeletionBlockReason(Model $model): ?string
    {
        return null;
    }

    protected function crudUsesSoftDeletes(): bool
    {
        return true;
    }

    protected function crudBeforeForceDelete(Model $model): void
    {
        // Override when related records need to be detached or cleaned up.
    }

    protected function crudDefaultSortField(): string
    {
        return $this->crudSortableFields()[0] ?? 'created_at';
    }

    protected function crudNotify(string $message, string $variant = 'success'): void
    {
        $this->dispatch('notify', text: $message, variant: $variant);
    }

    protected function applyTrashMode(Builder $query): Builder
    {
        if (! $this->crudUsesSoftDeletes()) {
            return $query;
        }

        return match ($this->trashMode) {
            'trashed' => $query->onlyTrashed(),
            'all' => $query->withTrashed(),
            default => $query,
        };
    }

    protected function applySorting(Builder $query): Builder
    {
        abort_unless(in_array($this->sortField, $this->crudSortableFields(), true), 422);
        abort_unless(in_array($this->sortDirection, ['asc', 'desc'], true), 422);

        return $query->orderBy($this->sortField, $this->sortDirection);
    }

    private function findCrudModel(int|string $id, bool $onlyTrashed = false): Model
    {
        $modelClass = $this->crudModelClass();
        $query = $modelClass::query();

        if ($onlyTrashed) {
            $query->onlyTrashed();
        }

        return $query->findOrFail($id);
    }

    private function selectedCrudModels(bool $onlyTrashed = false)
    {
        abort_if($this->selected === [], 422, 'Pilih minimal satu data.');

        $modelClass = $this->crudModelClass();
        $query = $modelClass::query();

        if ($onlyTrashed) {
            $query->onlyTrashed();
        }

        $models = $query->whereKey($this->selected)->get();
        abort_unless($models->count() === count(array_unique($this->selected)), 422);

        return $models;
    }

    private function resetPageAndSelection(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }
}
