<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('password dapat diperbarui', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    Livewire::actingAs($user)
        ->test('user-settings')
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('password saat ini harus benar untuk memperbarui password', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    Livewire::actingAs($user)
        ->test('user-settings')
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});
