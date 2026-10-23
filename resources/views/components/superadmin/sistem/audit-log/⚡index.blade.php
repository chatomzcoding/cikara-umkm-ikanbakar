<?php

use App\Livewire\Concerns\WithCrudTable;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    use WithCrudTable;

    public string $sortField = 'created_at';

    public array $filter = [
        'event' => '',
        'date_from' => '',
        'date_to' => '',
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', AuditLog::class);
    }

    protected function crudModelClass(): string
    {
        return AuditLog::class;
    }

    protected function crudSortableFields(): array
    {
        return ['created_at', 'event', 'user_name', 'auditable_type', 'ip_address'];
    }

    protected function crudDefaultSortField(): string
    {
        return 'created_at';
    }

    protected function crudExportPermission(): string
    {
        return 'audit-logs.export';
    }

    protected function crudUsesSoftDeletes(): bool
    {
        return false;
    }

    protected function crudExportColumns(): array
    {
        return [
            'Waktu' => 'created_at',
            'Pelaku' => 'user_name',
            'Email' => 'user_email',
            'Aktivitas' => 'event',
            'Deskripsi' => 'description',
            'Model' => fn (Model $log) => class_basename((string) $log->auditable_type),
            'ID Data' => 'auditable_id',
            'Nilai Lama' => fn (Model $log) => $log->old_values,
            'Nilai Baru' => fn (Model $log) => $log->new_values,
            'Metadata' => fn (Model $log) => $log->metadata,
            'IP' => 'ip_address',
        ];
    }

    protected function crudFilteredQuery(): Builder
    {
        $query = AuditLog::query()
            ->search($this->search)
            ->when($this->filter['event'] ?? null, fn (Builder $query, string $event) => $query->where('event', $event))
            ->when($this->filter['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($this->filter['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));

        return $this->applySorting($query);
    }

    #[Computed]
    public function items()
    {
        return $this->crudFilteredQuery()->paginate($this->perPage);
    }

    #[Computed]
    public function events()
    {
        return AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event');
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function resetCrudTable(): void
    {
        $this->reset(['search', 'perPage', 'filter']);
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }
};
?>

<div class="card">
    <div class="border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Riwayat Aktivitas Sistem</h2>
        <p class="text-xs text-slate-400">Audit bersifat read-only dan menyimpan perubahan sebelum serta sesudah.</p>
    </div>

    <div class="space-y-4 p-4 sm:p-5">
        <x-crud.toolbar export-permission="audit-logs.export" :show-trash="false" />

        <div class="grid gap-3 sm:grid-cols-3">
            <select wire:model.live="filter.event" class="form-select rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450">
                <option value="">Semua aktivitas</option>
                @foreach ($this->events as $event)
                    <option value="{{ $event }}">{{ str($event)->replace('_', ' ')->headline() }}</option>
                @endforeach
            </select>
            <input wire:model.live="filter.date_from" type="date" class="form-input rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450" aria-label="Tanggal mulai">
            <input wire:model.live="filter.date_to" type="date" class="form-input rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450" aria-label="Tanggal akhir">
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead><tr>
                    <x-crud.sortable-header field="created_at" label="Waktu" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-crud.sortable-header field="user_name" label="Pelaku" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-crud.sortable-header field="event" label="Aktivitas" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="bg-slate-200 px-4 py-3 font-semibold uppercase dark:bg-navy-800">Objek</th>
                    <th class="bg-slate-200 px-4 py-3 font-semibold uppercase dark:bg-navy-800">Deskripsi</th>
                    <th class="bg-slate-200 px-4 py-3 font-semibold uppercase dark:bg-navy-800">Perubahan</th>
                    <x-crud.sortable-header field="ip_address" label="IP" :sort-field="$sortField" :sort-direction="$sortDirection" />
                </tr></thead>
                <tbody>
                    @forelse ($this->items as $item)
                        <tr wire:key="audit-{{ $item->id }}" class="border-b border-slate-200 align-top dark:border-navy-500">
                            <td class="whitespace-nowrap px-4 py-3 text-xs">{{ $item->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3"><div>{{ $item->user_name ?: 'Sistem' }}</div><div class="text-xs text-slate-400">{{ $item->user_email }}</div></td>
                            <td class="px-4 py-3"><span class="badge bg-info/10 text-info">{{ str($item->event)->replace('_', ' ')->headline() }}</span></td>
                            <td class="px-4 py-3 text-xs"><div>{{ class_basename((string) $item->auditable_type) ?: '-' }}</div><div class="text-slate-400">ID {{ $item->auditable_id ?: '-' }}</div></td>
                            <td class="max-w-xs px-4 py-3">{{ $item->description }}</td>
                            <td class="px-4 py-3">
                                @if ($item->old_values || $item->new_values || $item->metadata)
                                    <details class="min-w-64 text-xs">
                                        <summary class="cursor-pointer font-medium text-primary">Lihat detail</summary>
                                        @if ($item->old_values)<p class="mt-2 font-semibold">Sebelum</p><pre class="mt-1 overflow-auto rounded bg-slate-100 p-2 dark:bg-navy-700">{{ json_encode($item->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>@endif
                                        @if ($item->new_values)<p class="mt-2 font-semibold">Sesudah</p><pre class="mt-1 overflow-auto rounded bg-slate-100 p-2 dark:bg-navy-700">{{ json_encode($item->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>@endif
                                        @if ($item->metadata)<p class="mt-2 font-semibold">Metadata</p><pre class="mt-1 overflow-auto rounded bg-slate-100 p-2 dark:bg-navy-700">{{ json_encode($item->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>@endif
                                    </details>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs">{{ $item->ip_address ?: '-' }}</td>
                        </tr>
                    @empty
                        <x-crud.empty-state colspan="7" message="Belum ada aktivitas audit." />
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $this->items->links() }}
    </div>
</div>
