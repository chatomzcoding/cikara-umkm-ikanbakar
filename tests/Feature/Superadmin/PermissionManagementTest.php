<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    $this->superadminRole = Role::create([
        'name' => 'superadmin',
        'display_name' => 'Super Admin',
    ]);
    $this->superadmin = User::factory()->create();
    $this->superadmin->addRole($this->superadminRole);
});

test('permission seeder membuat katalog dan memberikan seluruh akses kepada superadmin', function () {
    $this->seed(PermissionSeeder::class);

    $configuredPermissions = collect(config('permissions.groups'))
        ->flatMap(fn (array $group) => array_keys($group['permissions']))
        ->values();

    expect(Permission::whereIn('name', $configuredPermissions)->count())
        ->toBe($configuredPermissions->count())
        ->and($this->superadminRole->fresh()->permissions()->count())
        ->toBe($configuredPermissions->count());
});

test('role dapat memperoleh akses granular ke halaman tertentu', function () {
    $permission = Permission::create([
        'name' => 'users.view',
        'display_name' => 'Melihat pengguna',
    ]);
    $role = Role::create(['name' => 'viewer', 'display_name' => 'Viewer']);
    $role->givePermission($permission);
    $user = User::factory()->create();
    $user->addRole($role);

    $this->actingAs($user)
        ->get(route('superadmin.sistem.user.index'))
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('superadmin.sistem.role.index'))
        ->assertForbidden();
});

test('permission view tidak mengizinkan perubahan atau penghapusan user', function () {
    $permission = Permission::create(['name' => 'users.view']);
    $role = Role::create(['name' => 'viewer']);
    $role->givePermission($permission);
    $viewer = User::factory()->create();
    $viewer->addRole($role);
    $target = User::factory()->create();

    expect(Gate::forUser($viewer)->allows('viewAny', User::class))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('update', $target))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('delete', $target))->toBeFalse();

    Livewire::actingAs($viewer)
        ->test('superadmin.sistem.user.index')
        ->call('deleteItem', $target->id)
        ->assertForbidden();

    expect($target->fresh())->not->toBeNull();
});

test('superadmin dapat membuat role dan menetapkan permission melalui matriks', function () {
    $this->seed(PermissionSeeder::class);
    $permission = Permission::where('name', 'users.view')->sole();

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.role.index')
        ->call('create')
        ->set('form.name', 'operator')
        ->set('form.display_name', 'Operator')
        ->set('form.description', 'Operator pengguna')
        ->set('selectedPermissions', [(string) $permission->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $role = Role::where('name', 'operator')->sole();

    expect($role->hasPermission('users.view'))->toBeTrue();
});

test('pengelola role non-superadmin hanya dapat menjalankan kemampuan yang diberikan', function () {
    $this->seed(PermissionSeeder::class);

    $managerRole = Role::create(['name' => 'role-manager', 'display_name' => 'Role Manager']);
    $managerRole->syncPermissions(Permission::whereIn('name', [
        'roles.view',
        'roles.create',
        'roles.assign-permissions',
    ])->get());
    $manager = User::factory()->create();
    $manager->addRole($managerRole);
    $userView = Permission::where('name', 'users.view')->sole();

    Livewire::actingAs($manager)
        ->test('superadmin.sistem.role.index')
        ->call('create')
        ->set('form.name', 'auditor')
        ->set('form.display_name', 'Auditor')
        ->set('selectedPermissions', [(string) $userView->id])
        ->call('save')
        ->assertHasNoErrors();

    expect(Role::where('name', 'auditor')->sole()->hasPermission('users.view'))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('users.delete'))->toBeFalse();
});

test('role sistem tidak dapat diedit atau dihapus', function () {
    $this->seed(PermissionSeeder::class);

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.role.index')
        ->call('edit', $this->superadminRole->id)
        ->assertForbidden();

    expect($this->superadminRole->fresh())->not->toBeNull();
});

test('role yang masih digunakan tidak dapat dihapus', function () {
    $role = Role::create(['name' => 'operator', 'display_name' => 'Operator']);
    User::factory()->create()->addRole($role);

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.role.index')
        ->call('delete', $role->id)
        ->assertDispatched('notify');

    expect($role->fresh())->not->toBeNull();
});
