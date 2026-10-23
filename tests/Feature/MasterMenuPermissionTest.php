<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->addRole(Role::where('name', 'admin')->firstOrFail());
});

test('role admin dapat membuka halaman menu master', function () {
    $this->actingAs($this->admin)
        ->get(route('superadmin.sistem.data-pokok.index'))
        ->assertOk()
        ->assertSee(route('superadmin.sistem.data-pokok.index'), false);

    $this->actingAs($this->admin)
        ->get(route('superadmin.sistem.audit-log.index'))
        ->assertOk();

    $this->actingAs($this->admin)
        ->get(route('superadmin.sistem.user.index'))
        ->assertOk();
});

test('halaman error, kategori, dan role tetap khusus superadmin', function () {
    $this->actingAs($this->admin)
        ->get(route('superadmin.sistem.error-page.index'))
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->get(route('superadmin.sistem.kategori.index'))
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->get(route('superadmin.sistem.role.index'))
        ->assertForbidden();
});

test('seeder permission tidak menghapus permission tambahan yang diberikan manual ke admin', function () {
    $admin = Role::where('name', 'admin')->firstOrFail();
    $admin->permissions()->syncWithoutDetaching(
        Permission::where('name', 'categories.view')->pluck('id'),
    );

    $this->seed(PermissionSeeder::class);

    expect($admin->permissions()->where('name', 'categories.view')->exists())->toBeTrue();
});
