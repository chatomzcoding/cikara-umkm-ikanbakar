<?php

use App\Models\User;
use Livewire\Livewire;

test('halaman pengaturan user dapat dibuka', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('user.settings'))
        ->assertOk();
});

test('nama profil dapat diperbarui tanpa mengubah email', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('user-settings')
        ->set('name', 'Nama Baru')
        ->call('updateProfile')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Nama Baru')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('nama profil wajib diisi', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('user-settings')
        ->set('name', '')
        ->call('updateProfile')
        ->assertHasErrors(['name']);
});
