<?php

use App\Livewire\Concerns\WithCrudTable;
use App\Models\Role;
use App\Models\User;
use App\Services\UserLifecycleService;
use App\Services\UserImpersonationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use WithCrudTable {
        resetCrudTable as resetBaseCrudTable;
    }

    public $id;

    public $leftComponent;

    public array $filter = ['role_id' => 'semua', 'status' => 'semua'];

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    protected function crudModelClass(): string
    {
        return User::class;
    }

    protected function crudSortableFields(): array
    {
        return ['name', 'email', 'username', 'created_at'];
    }

    protected function crudExportPermission(): string
    {
        return 'users.export';
    }

    protected function crudExportColumns(): array
    {
        return [
            'Nama' => 'name',
            'Email' => 'email',
            'Username' => 'username',
            'Role' => fn (Model $user) => $user->roles->pluck('display_name')->implode(', '),
            'Status' => fn (Model $user) => $user->status->label(),
            'Dibuat' => 'created_at',
            'Dihapus' => 'deleted_at',
        ];
    }

    protected function crudFilteredQuery(): Builder
    {
        $roleId = $this->filter['role_id'] ?? 'semua';
        $status = $this->filter['status'] ?? 'semua';
        $query = User::query()
            ->with('roles')
            ->search($this->search)
            ->when($roleId !== 'semua', fn (Builder $query) => $query->whereHas(
                'roles',
                fn (Builder $roleQuery) => $roleQuery->where('roles.id', $roleId),
            ))
            ->when($status !== 'semua', fn (Builder $query) => $query->where('status', $status));

        return $this->applySorting($this->applyTrashMode($query));
    }

    #[Computed]
    public function items()
    {
        return $this->crudFilteredQuery()->paginate($this->perPage);
    }

    #[Computed]
    public function roles()
    {
        return Role::query()->orderBy('display_name')->get();
    }

    public function buttonEdit(int $id): void
    {
        Gate::authorize('update', User::findOrFail($id));
        $this->leftComponent = 'superadmin.sistem.user.form';
        $this->dispatch('sistem-user-edit', $id);
    }

    public function resetCrudTable(): void
    {
        $this->resetBaseCrudTable();
        $this->filter = ['role_id' => 'semua', 'status' => 'semua'];
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    #[On('handle-form')]
    public function handleForm(): void
    {
        $this->resetPage();
    }

    #[On('user-suspend')]
    public function suspendUser(int $id, string $reason = ''): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('suspend', $user);
        $this->runLifecycleAction(fn () => app(UserLifecycleService::class)->suspend($user, auth()->user(), $reason), 'Pengguna berhasil ditangguhkan.');
    }

    #[On('user-activate')]
    public function activateUser(int $id): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('activate', $user);
        $this->runLifecycleAction(fn () => app(UserLifecycleService::class)->activate($user, auth()->user()), 'Pengguna berhasil diaktifkan kembali.');
    }

    #[On('user-deactivate')]
    public function deactivateUser(int $id, string $reason = ''): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('deactivate', $user);
        $this->runLifecycleAction(fn () => app(UserLifecycleService::class)->deactivate($user, auth()->user(), $reason), 'Pengguna berhasil dinonaktifkan.');
    }

    #[On('user-impersonate')]
    public function impersonateUser(int $id): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('impersonate', $user);

        try {
            app(UserImpersonationService::class)->start(auth()->user(), $user);
            $this->redirectRoute('dashboard');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->dispatch('notify', text: collect($exception->errors())->flatten()->first(), variant: 'error');
        }
    }

    private function runLifecycleAction(callable $action, string $message): void
    {
        try {
            $action();
            $this->dispatch('notify', text: $message, variant: 'success');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->dispatch('notify', text: collect($exception->errors())->flatten()->first(), variant: 'error');
        }
    }

    protected function crudDeletionBlockReason(Model $model): ?string
    {
        if (auth()->id() === $model->getKey()) {
            return 'Anda tidak dapat menghapus akun yang sedang digunakan.';
        }

        $isLastSuperadmin = $model->hasRole('superadmin')
            && User::query()->whereHas('roles', fn (Builder $query) => $query->where('name', 'superadmin'))->count() <= 1;

        return $isLastSuperadmin ? 'Super Admin terakhir tidak dapat dihapus.' : null;
    }

    protected function crudBeforeForceDelete(Model $model): void
    {
        $model->roles()->detach();
        $model->permissions()->detach();
    }
};
?>

<div x-data="{ openForm: false }">
    <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
        <div class="col-span-12 lg:col-span-4" x-show="openForm" x-cloak>
            @include('layouts.form')
        </div>

        <div class="col-span-12" :class="openForm ? 'lg:col-span-8' : 'lg:col-span-12'">
            <div class="card relative">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
                    <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">Daftar User</h2>
                    <div class="flex gap-2">
                        @can('create', App\Models\User::class)
                        <button type="button" class="btn h-8 gap-1.5 bg-primary px-3 font-medium text-white"
                            @click="openForm = true" wire:click="$set('leftComponent', 'superadmin.sistem.user.form')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            Tambah
                        </button>
                        @endcan
                        @can('viewAny', App\Models\Role::class)
                        <a href="{{ route('superadmin.sistem.role.index') }}"
                            class="btn h-8 gap-1.5 bg-secondary px-3 font-medium text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="m17 11 2 2 4-4" />
                            </svg>
                            Role & Permission
                        </a>
                        @endcan
                    </div>
                </div>

                <div class="space-y-4 p-4 sm:p-5">
                    <x-crud.toolbar export-permission="users.export" />

                    <div class="grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
                        <label class="block text-xs text-slate-500">
                            <select wire:model.live="filter.role_id"
                                class="form-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-navy-450 dark:bg-navy-700">
                                <option value="semua">Semua role</option>
                                @foreach ($this->roles as $role)
                                <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                @endforeach
                            </select>
                            Filter role
                        </label>
                        <label class="block text-xs text-slate-500">
                            <select wire:model.live="filter.status"
                                class="form-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-navy-450 dark:bg-navy-700">
                                <option value="semua">Semua status</option>
                                @foreach (App\Enums\UserStatus::cases() as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                            Filter status
                        </label>
                    </div>

                    <x-crud.bulk-actions :selected="$selected" :trash-mode="$trashMode" delete-permission="users.delete"
                        restore-permission="users.restore" force-delete-permission="users.force-delete" />

                    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                        <table class="is-hoverable w-full text-left">
                            <thead>
                                <tr>
                                    <th class="w-10 rounded-l-lg bg-slate-200 px-3 py-3 text-center dark:bg-navy-800">
                                        @if ($trashMode !== 'all')
                                        <x-crud.select-page :ids="$this->items->pluck('id')" :selected="$selected" />
                                        @endif
                                    </th>
                                    <x-crud.sortable-header field="name" label="Nama" :sort-field="$sortField"
                                        :sort-direction="$sortDirection" />
                                    <x-crud.sortable-header field="email" label="Email" :sort-field="$sortField"
                                        :sort-direction="$sortDirection" />
                                    <x-crud.sortable-header field="username" label="Username" :sort-field="$sortField"
                                        :sort-direction="$sortDirection" />
                                    <th
                                        class="whitespace-nowrap bg-slate-200 px-4 py-3 text-center font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">
                                        Role</th>
                                    <th
                                        class="whitespace-nowrap bg-slate-200 px-4 py-3 text-center font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">
                                        Status</th>
                                    <th
                                        class="whitespace-nowrap rounded-r-lg bg-slate-200 px-4 py-3 text-center font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->items as $item)
                                <tr wire:key="user-{{ $item->id }}-{{ $item->deleted_at?->timestamp ?? 'active' }}"
                                    class="border-b border-slate-200 dark:border-navy-500">
                                    <td class="px-3 py-3 text-center">
                                        @if ($trashMode !== 'all')
                                        <x-crud.row-checkbox :id="$item->id" />
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $item->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $item->email }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $item->username }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @foreach ($item->roles as $role)
                                        <span class="badge rounded-full bg-info text-white">{{ $role->display_name
                                            }}</span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($item->trashed())
                                        <span class="badge bg-error/10 text-error">Sampah</span>
                                        @else
                                        <span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center">
                                        @if ($item->trashed())
                                        @can('restore', $item)
                                        <button type="button" wire:click="restoreItem({{ $item->id }})"
                                            class="btn h-8 gap-1.5 bg-success px-3 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 12a9 9 0 1 0 3-6.7L3 8" />
                                                <path d="M3 3v5h5" />
                                            </svg>
                                            Pulihkan
                                        </button>
                                        @endcan
                                        @can('forceDelete', $item)
                                        <button type="button" wire:click="$dispatch('swal-delete', {
                                                            id: {{ $item->id }},
                                                            name: 'crud-force-delete-item',
                                                            title: 'Hapus permanen user?',
                                                            text: @js('User '.$item->name.' akan dihapus permanen dan tidak dapat dipulihkan.'),
                                                            confirmButtonText: 'Ya, hapus permanen!'
                                                        })" class="btn h-8 gap-1.5 bg-error px-3 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="M19 6l-1 14H6L5 6" />
                                                <path d="m10 11 4 5M14 11l-4 5" />
                                            </svg>
                                            Hapus Permanen
                                        </button>
                                        @endcan
                                        @else
                                        @can('update', $item)
                                        <button type="button" @click="openForm = true"
                                            wire:click="buttonEdit({{ $item->id }})"
                                            class="btn h-8 gap-1.5 bg-primary px-3 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 20h9" />
                                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                            </svg>
                                            Edit
                                        </button>
                                        @endcan
                                        @if ($item->status === App\Enums\UserStatus::Active && auth()->id() !== $item->id)
                                        @can('impersonate', $item)
                                        <button type="button" wire:click="$dispatch('swal-delete', {
                                                            id: {{ $item->id }},
                                                            name: 'user-impersonate',
                                                            title: 'Masuk sebagai pengguna ini?',
                                                            text: @js('Anda akan menggunakan sistem sebagai '.$item->name.'.'),
                                                            confirmButtonText: 'Ya, impersonate'
                                                        })" class="btn h-8 gap-1.5 bg-secondary px-3 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                                <circle cx="9" cy="7" r="4"/>
                                                <path d="m17 11 2 2 4-4"/>
                                            </svg>
                                            Impersonate
                                        </button>
                                        @endcan
                                        @endif
                                        @if ($item->status === App\Enums\UserStatus::Active)
                                        @can('suspend', $item)
                                        <button type="button" wire:click="$dispatch('swal-delete', {
                                                            id: {{ $item->id }},
                                                            name: 'user-suspend',
                                                            title: 'Tangguhkan pengguna?',
                                                            text: @js('Akses '.$item->name.' akan dihentikan sementara.'),
                                                            confirmButtonText: 'Ya, tangguhkan',
                                                            input: 'textarea',
                                                            inputKey: 'reason',
                                                            inputLabel: 'Alasan penangguhan',
                                                            inputPlaceholder: 'Tuliskan alasan...'
                                                        })" class="btn h-8 gap-1.5 bg-warning px-3 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/>
                                            </svg>
                                            Suspend
                                        </button>
                                        @endcan
                                        @can('deactivate', $item)
                                        <button type="button" wire:click="$dispatch('swal-delete', {
                                                            id: {{ $item->id }},
                                                            name: 'user-deactivate',
                                                            title: 'Nonaktifkan pengguna?',
                                                            text: @js('Akses '.$item->name.' akan dihentikan sampai diaktifkan kembali.'),
                                                            confirmButtonText: 'Ya, nonaktifkan',
                                                            input: 'textarea',
                                                            inputKey: 'reason',
                                                            inputLabel: 'Alasan penonaktifan',
                                                            inputPlaceholder: 'Tuliskan alasan...'
                                                        })" class="btn h-8 gap-1.5 bg-error px-3 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <circle cx="12" cy="12" r="9"/><path d="m8 8 8 8"/>
                                            </svg>
                                            Nonaktifkan
                                        </button>
                                        @endcan
                                        @else
                                        @can('activate', $item)
                                        <button type="button" wire:click="$dispatch('swal-delete', {
                                                            id: {{ $item->id }},
                                                            name: 'user-activate',
                                                            title: 'Aktifkan kembali pengguna?',
                                                            text: @js('Akses '.$item->name.' akan dipulihkan.'),
                                                            confirmButtonText: 'Ya, aktifkan'
                                                        })" class="btn h-8 gap-1.5 bg-success px-3 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M20 6 9 17l-5-5"/>
                                            </svg>
                                            Aktifkan
                                        </button>
                                        @endcan
                                        @endif
                                        @can('delete', $item)
                                        <button type="button" wire:click="$dispatch('swal-delete', {
                                                            id: {{ $item->id }},
                                                            name: 'crud-delete-item',
                                                            title: 'Pindahkan user ke sampah?',
                                                            text: @js('User '.$item->name.' dapat dipulihkan kembali dari menu Sampah.'),
                                                            confirmButtonText: 'Ya, pindahkan!'
                                                        })" class="btn h-8 gap-1.5 bg-error px-3 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="M19 6l-1 14H6L5 6" />
                                                <path d="M10 11v5M14 11v5" />
                                            </svg>
                                            Hapus
                                        </button>
                                        @endcan
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <x-crud.empty-state colspan="7" message="User tidak ditemukan." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $this->items->links() }}
                </div>

                <div wire:loading.flex
                    wire:target="search,perPage,trashMode,filter,sortBy,deleteItem,restoreItem,forceDeleteItem,bulkDelete,bulkRestore,bulkForceDelete,suspendUser,activateUser,deactivateUser,impersonateUser"
                    class="absolute inset-0 z-20 items-center justify-center rounded-lg bg-white/60 dark:bg-navy-800/60">
                    <span class="rounded bg-white px-3 py-2 text-sm shadow dark:bg-navy-700">Memproses...</span>
                </div>
            </div>
        </div>
    </div>
</div>
