<?php

use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public $id;

    public $lock = true;

    public $form = [
        'name' => null,
        'username' => null,
        'email' => null,
        'password' => null,
    ];

    public array $role = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.username' => ['nullable', 'string', 'alpha_dash', 'max:255', Rule::unique('users', 'username')->ignore($this->id)],
            'form.email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->id)],
            'form.password' => [$this->id ? 'nullable' : 'required', Password::defaults()],
            'role' => ['array'],
            'role.*' => ['integer', Rule::exists('roles', 'id')],
        ];
    }

    #[Computed]
    public function roles()
    {
        return Role::query()->orderBy('display_name')->get();
    }

    #[On('sistem-user-edit')]
    public function buttonEdit($id): void
    {
        $data = User::findOrFail($id);
        Gate::authorize('update', $data);
        $this->id = $data->getKey();
        $this->lock = false;
        $this->form = [
            'name' => $data->name,
            'email' => $data->email,
            'username' => $data->username,
            'password' => null,
        ];
        $this->role = $data->roles->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function buttonBatalkan(): void
    {
        $this->reset(['form', 'id', 'role', 'lock']);
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate();
        $user = $this->id ? User::findOrFail($this->id) : new User;
        Gate::authorize($user->exists ? 'update' : 'create', $user->exists ? $user : User::class);

        if ($this->wouldRemoveLastSuperadmin($user, $validated['role'])) {
            $this->addError('role', 'Minimal satu pengguna harus tetap memiliki role Super Admin.');

            return;
        }

        $currentRoles = $user->exists
            ? $user->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->sort()->values()->all()
            : [];
        $newRoles = collect($validated['role'])->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($currentRoles !== $newRoles) {
            Gate::authorize('assignRoles', $user);
        }

        $data = $validated['form'];
        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->fill($data);
        if ($user->exists && $user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();
        $user->roles()->sync($newRoles);

        if ($currentRoles !== $newRoles) {
            app(AuditLogger::class)->record(
                event: 'roles_synced',
                description: 'Role pengguna diperbarui',
                subject: $user,
                oldValues: ['role_ids' => $currentRoles],
                newValues: ['role_ids' => $newRoles],
            );
        }

        $info = $this->id ? 'diperbarui' : 'ditambahkan';
        $this->reset(['form', 'id', 'role', 'lock']);

        $this->dispatch('notify',
            text: "Data User berhasil $info"
        );

        $this->dispatch('handle-form');
    }

    private function wouldRemoveLastSuperadmin(User $user, array $roleIds): bool
    {
        if (! $user->exists || ! $user->hasRole('superadmin')) {
            return false;
        }

        $keepsSuperadminRole = Role::query()
            ->whereKey($roleIds)
            ->where('name', 'superadmin')
            ->exists();

        if ($keepsSuperadminRole) {
            return false;
        }

        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'superadmin'))
            ->count() <= 1;
    }
};
?>

<div>
    @include('layouts.button-form',['judul' => 'User'])
    <form wire:submit='save'>
        <label class="block">
                    <span>Nama:</span>
                    <input
                        class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                        placeholder="Masukkan Nama"
                        type="text"
                        wire:model='form.name'
                        required
                    />
                    @error('form.name')
                            <span class="text-tiny-plus text-error">{{ $message }}</span>
                    @enderror
        </label>
        <label class="block">
            <span>Email:</span>
            <input
                class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                placeholder="Masukkan Email"
                type="email"
                wire:model='form.email'
                required
            />
            @error('form.email')
                    <span class="text-tiny-plus text-error">{{ $message }}</span>
            @enderror
        </label>
        <label class="block">
            <span>Username:</span>
            <input
                class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                placeholder="Masukkan Username"
                type="text"
                wire:model='form.username'
            />
            @error('form.username')
                    <span class="text-tiny-plus text-error">{{ $message }}</span>
            @enderror
        </label>
        @can('assignRoles', $id ? App\Models\User::find($id) : new App\Models\User)
            <label class="block">
                <span>Role</span>
                <select
                    x-init="$el._tom = new Tom($el)"
                    class="mt-1.5 w-full"
                    multiple
                    placeholder="Pilih Role"
                    autocomplete="off"
                    wire:model='role'
                    >
                    <option value="">Pilih Role</option>
                    @foreach ($this->roles as $item)
                        <option value="{{ $item->id }}">{{ $item->display_name }}</option>
                    @endforeach
                </select>
                @error('role')
                    <span class="text-tiny-plus text-error">{{ $message }}</span>
                @enderror
                @error('role.*')
                    <span class="text-tiny-plus text-error">{{ $message }}</span>
                @enderror
            </label>
        @endcan
        <label class="block mt-2">
            <span>Password:</span>
            <input
                class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                placeholder="********"
                type="password"
                minlength="8"
                autocomplete="new-password"
                wire:model='form.password'
                @required($id == null)
            />
            @error('form.password')
                <span class="text-tiny-plus text-error">{{ $message }}</span>
            @enderror
        </label>
        @include('layouts.button-form-footer')
    </form>
</div>
