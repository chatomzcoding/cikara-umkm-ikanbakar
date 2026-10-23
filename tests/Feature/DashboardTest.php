<?php

use App\Models\Role;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Informasi pengguna')
        ->assertSee($user->name)
        ->assertSee('Pengguna')
        ->assertDontSee('Ringkasan sistem');
});

test('superadmin can see the system overview', function () {
    $role = Role::create([
        'name' => 'superadmin',
        'display_name' => 'Super Admin',
    ]);
    $user = User::factory()->create();
    $user->addRole($role);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Super Admin')
        ->assertSee('Ringkasan sistem')
        ->assertSee('Total pengguna')
        ->assertSee('Role tersedia');
});
