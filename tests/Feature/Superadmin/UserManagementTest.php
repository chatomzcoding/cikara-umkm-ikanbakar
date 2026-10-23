<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->superadminRole = Role::create([
        'name' => 'superadmin',
        'display_name' => 'Super Admin',
    ]);

    $this->superadmin = User::factory()->create();
    $this->superadmin->addRole($this->superadminRole);
});

test('pengguna biasa tidak dapat mengakses halaman superadmin', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('superadmin.sistem.user.index'))
        ->assertForbidden();
});

test('superadmin dapat mengakses halaman pengelolaan user', function () {
    $this->actingAs($this->superadmin)
        ->get(route('superadmin.sistem.user.index'))
        ->assertSuccessful();
});

test('komponen pengelolaan user menolak pengguna biasa', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('superadmin.sistem.user.form')
        ->assertForbidden();
});

test('form user memvalidasi input dan menyimpan username', function () {
    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.form')
        ->set('form.name', '')
        ->set('form.email', 'bukan-email')
        ->set('form.username', 'username tidak valid')
        ->set('form.password', 'pendek')
        ->call('save')
        ->assertHasErrors([
            'form.name' => 'required',
            'form.email' => 'email',
            'form.username' => 'alpha_dash',
            'form.password',
        ]);

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.form')
        ->set('form.name', 'User Baru')
        ->set('form.email', 'user.baru@example.test')
        ->set('form.username', 'user_baru')
        ->set('form.password', 'Password-Aman-123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $user = User::where('email', 'user.baru@example.test')->sole();

    expect($user->username)->toBe('user_baru')
        ->and(Hash::check('Password-Aman-123', $user->password))->toBeTrue();
});

test('superadmin tidak dapat menghapus akun yang sedang digunakan', function () {
    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->call('deleteItem', $this->superadmin->id)
        ->assertDispatched('notify');

    expect($this->superadmin->fresh())->not->toBeNull();
});

test('role superadmin tidak dapat dilepas dari superadmin terakhir', function () {
    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.form')
        ->dispatch('sistem-user-edit', $this->superadmin->id)
        ->set('role', [])
        ->call('save')
        ->assertHasErrors('role');

    expect($this->superadmin->fresh()->hasRole('superadmin'))->toBeTrue();
});

test('hapus user menggunakan soft delete dan mempertahankan relasinya', function () {
    $role = Role::create(['name' => 'operator', 'display_name' => 'Operator']);
    $target = User::factory()->create();
    $target->addRole($role);

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->call('deleteItem', $target->id)
        ->assertDispatched('notify');

    $this->assertSoftDeleted('users', ['id' => $target->id]);
    expect($target->roles()->whereKey($role->id)->exists())->toBeTrue();
});

test('user di sampah dapat ditampilkan dan dipulihkan', function () {
    $target = User::factory()->create(['name' => 'User Sampah']);
    $target->delete();

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->set('trashMode', 'trashed')
        ->assertSee('User Sampah')
        ->call('restoreItem', $target->id)
        ->assertDispatched('notify');

    $this->assertNotSoftDeleted('users', ['id' => $target->id]);
});

test('bulk action dapat menghapus dan memulihkan beberapa user', function () {
    $targets = User::factory()->count(2)->create();
    $ids = $targets->pluck('id')->all();

    $component = Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->set('selected', $ids)
        ->call('bulkDelete')
        ->assertDispatched('notify');

    foreach ($ids as $id) {
        $this->assertSoftDeleted('users', ['id' => $id]);
    }

    $component
        ->set('trashMode', 'trashed')
        ->set('selected', $ids)
        ->call('bulkRestore')
        ->assertDispatched('notify');

    foreach ($ids as $id) {
        $this->assertNotSoftDeleted('users', ['id' => $id]);
    }
});

test('hapus permanen membersihkan user dan pivot role', function () {
    $role = Role::create(['name' => 'staff', 'display_name' => 'Staff']);
    $target = User::factory()->create();
    $target->addRole($role);
    $target->delete();

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->set('trashMode', 'trashed')
        ->call('forceDeleteItem', $target->id)
        ->assertDispatched('notify');

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
    $this->assertDatabaseMissing('role_user', ['user_id' => $target->id]);
});

test('export CSV tersedia dan mendukung data terpilih', function () {
    $target = User::factory()->create(['name' => '=Formula Berbahaya']);

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->set('selected', [$target->id])
        ->call('exportCsv')
        ->assertFileDownloaded();
});
