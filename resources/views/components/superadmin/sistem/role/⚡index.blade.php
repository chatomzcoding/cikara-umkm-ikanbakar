<?php

use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public ?int $id = null;

    public string $search = '';

    public bool $showForm = false;

    public array $form = [
        'name' => '',
        'display_name' => '',
        'description' => '',
    ];

    public array $selectedPermissions = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', Role::class);
    }

    protected function rules(): array
    {
        return [
            'form.name' => [
                'required',
                'string',
                'alpha_dash',
                'max:255',
                Rule::notIn(Role::SYSTEM_ROLES),
                Rule::unique('roles', 'name')->ignore($this->id),
            ],
            'form.display_name' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:1000'],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ];
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount(['permissions', 'users'])
            ->when($this->search, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('display_name', 'like', "%{$this->search}%");
                });
            })
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', ['superadmin'])
            ->orderBy('display_name')
            ->paginate(10);
    }

    #[Computed]
    public function permissionGroups(): array
    {
        $permissions = Permission::query()->get()->keyBy('name');

        return collect(config('permissions.groups', []))
            ->map(function (array $group) use ($permissions): array {
                $items = collect($group['permissions'])
                    ->map(function (string $displayName, string $name) use ($permissions): ?array {
                        $permission = $permissions->get($name);

                        return $permission ? [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'display_name' => $displayName,
                        ] : null;
                    })
                    ->filter()
                    ->values()
                    ->all();

                return ['label' => $group['label'], 'permissions' => $items];
            })
            ->all();
    }

    public function create(): void
    {
        Gate::authorize('create', Role::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $role = Role::with('permissions')->findOrFail($id);

        if ($role->isSystem()) {
            abort(403);
        }

        Gate::authorize('update', $role);

        $this->id = $role->id;
        $this->form = [
            'name' => $role->name,
            'display_name' => $role->display_name ?? '',
            'description' => $role->description ?? '',
        ];
        $this->selectedPermissions = $role->permissions->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->showForm = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate();
        $role = $this->id ? Role::findOrFail($this->id) : new Role;

        Gate::authorize($role->exists ? 'update' : 'create', $role->exists ? $role : Role::class);

        if ($role->isSystem()) {
            abort(403);
        }

        $currentPermissions = $role->exists
            ? $role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->sort()->values()->all()
            : [];
        $newPermissions = collect($validated['selectedPermissions'])->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($currentPermissions !== $newPermissions) {
            Gate::authorize('assignPermissions', $role);
        }

        $role->fill($validated['form'])->save();
        $role->syncPermissions($newPermissions);

        if ($currentPermissions !== $newPermissions) {
            app(AuditLogger::class)->record(
                event: 'permissions_synced',
                description: 'Permission role diperbarui',
                subject: $role,
                oldValues: ['permission_ids' => $currentPermissions],
                newValues: ['permission_ids' => $newPermissions],
            );
        }

        $this->resetForm();
        $this->dispatch('notify', text: 'Role dan permission berhasil disimpan.');
    }

    #[On('delete-role')]
    public function delete(int $id): void
    {
        $role = Role::withCount('users')->findOrFail($id);
        Gate::authorize('delete', $role);

        if ($role->isSystem()) {
            abort(403);
        }

        if ($role->users_count > 0) {
            $this->dispatch('notify', text: 'Role masih digunakan dan tidak dapat dihapus.', variant: 'error');

            return;
        }

        $role->delete();
        $this->dispatch('notify', text: 'Role berhasil dihapus.', variant: 'deleted');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->reset(['id', 'form', 'selectedPermissions', 'showForm']);
        $this->resetValidation();
    }
};
?>

<div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
    @if ($showForm)
        <div class="col-span-12 xl:col-span-5">
            <div class="card">
                <div class="flex items-center justify-between border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
                    <div>
                        <h2 class="font-medium text-slate-700 dark:text-navy-100">{{ $id ? 'Ubah Role' : 'Tambah Role' }}</h2>
                        <p class="text-xs text-slate-400">Pilih permission yang dibutuhkan role ini.</p>
                    </div>
                    <button type="button" wire:click="cancel" class="btn size-8 rounded-full p-0 hover:bg-slate-150">&times;</button>
                </div>

                <form wire:submit="save" class="space-y-4 p-4 sm:p-5">
                    <div class="rounded-lg bg-info/10 px-3 py-2 text-xs text-info">
                        Centang permission yang ingin diberikan, lalu tekan <strong>Simpan</strong>. Perubahan ini hanya berlaku untuk role non-sistem.
                    </div>
                    <label class="block">
                        <span>Nama sistem</span>
                        <input wire:model="form.name" type="text" placeholder="contoh: editor" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2">
                        @error('form.name') <span class="text-xs text-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span>Nama tampilan</span>
                        <input wire:model="form.display_name" type="text" placeholder="contoh: Editor" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2">
                        @error('form.display_name') <span class="text-xs text-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span>Deskripsi</span>
                        <textarea wire:model="form.description" rows="2" class="form-textarea mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2"></textarea>
                        @error('form.description') <span class="text-xs text-error">{{ $message }}</span> @enderror
                    </label>

                    <div class="space-y-3">
                        <div>
                            <h3 class="font-medium text-slate-700 dark:text-navy-100">Permission</h3>
                            <p class="text-xs text-slate-400">Hak akses langsung yang diperoleh semua pengguna role ini.</p>
                        </div>
                        @foreach ($this->permissionGroups as $group)
                            <fieldset class="rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                                <legend class="px-1 text-sm font-semibold text-slate-700 dark:text-navy-100">{{ $group['label'] }}</legend>
                                <div class="space-y-2">
                                    @foreach ($group['permissions'] as $permission)
                                        <label wire:key="permission-{{ $permission['id'] }}" for="permission-{{ $permission['id'] }}" class="flex cursor-pointer select-none items-start gap-3 rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-navy-600">
                                            <input
                                                id="permission-{{ $permission['id'] }}"
                                                type="checkbox"
                                                wire:model.live="selectedPermissions"
                                                value="{{ $permission['id'] }}"
                                                class="mt-0.5 size-5 shrink-0 cursor-pointer rounded border-slate-400 accent-indigo-600"
                                            >
                                            <span>
                                                <span class="block text-sm">{{ $permission['display_name'] }}</span>
                                                <span class="block text-xs text-slate-400">{{ $permission['name'] }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                        @error('selectedPermissions') <span class="text-xs text-error">{{ $message }}</span> @enderror
                        @error('selectedPermissions.*') <span class="text-xs text-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="cancel" class="btn h-9 border border-slate-300 px-4">Batal</button>
                        <button type="submit" class="btn h-9 bg-primary px-4 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="col-span-12 {{ $showForm ? 'xl:col-span-7' : '' }}">
        <div class="card">
            <div class="flex flex-col gap-3 border-b border-slate-200 p-4 dark:border-navy-500 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <h2 class="font-medium text-slate-700 dark:text-navy-100">Daftar Role</h2>
                    <p class="text-xs text-slate-400">Buat atau pilih role biasa untuk mengatur permission. Super Admin selalu memiliki seluruh akses.</p>
                </div>
                <div class="flex gap-2">
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari role..." class="form-input h-9 rounded-lg border border-slate-300 bg-transparent px-3">
                    @can('create', App\Models\Role::class)
                        <button type="button" wire:click="create" class="btn h-9 bg-primary px-4 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus size-4.5"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                            Role</button>
                    @endcan
                </div>
            </div>

            <div class="overflow-x-auto p-4 sm:p-5">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-200 text-xs uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">
                            <th class="rounded-l-lg px-4 py-3">Role</th>
                            <th class="px-4 py-3 text-center">Permission</th>
                            <th class="px-4 py-3 text-center">Pengguna</th>
                            <th class="rounded-r-lg px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->roles as $role)
                            <tr wire:key="role-{{ $role->id }}" class="border-b border-slate-200 dark:border-navy-500">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $role->display_name ?: $role->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $role->name }}</div>
                                    @if ($role->isSystem())
                                        <span class="badge mt-1 bg-info/10 text-info">Role sistem</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">{{ $role->isSystem() ? 'Semua' : $role->permissions_count }}</td>
                                <td class="px-4 py-3 text-center">{{ $role->users_count }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if (! $role->isSystem())
                                        @can('update', $role)
                                            <button type="button" wire:click="edit({{ $role->id }})" class="btn size-9 p-0 font-medium text-success hover:bg-success/20 focus:bg-success/20 active:bg-success/25" title="Ubah role">
                                                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415" /><path d="M16 5l3 3" /></svg>
                                            </button>
                                        @endcan
                                        @can('delete', $role)
                                            <button
                                                type="button"
                                                wire:click="$dispatch('swal-delete', {
                                                    id: {{ $role->id }},
                                                    name: 'delete-role',
                                                    title: 'Hapus role?',
                                                    text: @js('Role '.$role->display_name.' akan dihapus dan tidak dapat dikembalikan.'),
                                                    confirmButtonText: 'Ya, hapus!'
                                                })"
                                                class="btn size-9 p-0 font-medium text-error hover:bg-error/20 focus:bg-error/20 active:bg-error/25"
                                                title="Hapus role"
                                            >
                                            <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="size-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    />
                                                </svg>
                                        </button>
                                        @endcan
                                    @else
                                        <span class="text-xs text-slate-400">Dilindungi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Role tidak ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $this->roles->links() }}</div>
            </div>
        </div>
    </div>
</div>
