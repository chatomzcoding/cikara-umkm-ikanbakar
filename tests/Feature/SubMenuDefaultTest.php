<?php

use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;
use Modules\Superadmin\Models\DataPokok;

beforeEach(function () {
    $role = Role::create(['name' => 'superadmin', 'display_name' => 'Super Admin']);
    $this->superadmin = User::factory()->create();
    $this->superadmin->addRole($role);
});

test('sub menu sidebar tersembunyi secara default', function () {
    $this->actingAs($this->superadmin)
        ->get(route('superadmin.sistem.data-pokok.index'))
        ->assertOk()
        ->assertSee('<body class="is-header-blur">', false);
});

test('sub menu sidebar ditampilkan jika pengaturan data pokok diatur show', function () {
    DataPokok::create(['singleton_key' => 1, 'nama_aplikasi' => 'Test', 'sub_menu_default' => 'show']);

    $this->actingAs($this->superadmin)
        ->get(route('superadmin.sistem.data-pokok.index'))
        ->assertOk()
        ->assertSee('is-sidebar-open', false);
});

test('form data pokok menyimpan pengaturan sub menu', function () {
    DataPokok::create(['singleton_key' => 1, 'nama_aplikasi' => 'Test']);

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.data-pokok.form')
        ->set('form.nama_aplikasi', 'Test')
        ->set('form.sub_menu_default', 'show')
        ->call('save')
        ->assertHasNoErrors();

    expect(DataPokok::query()->value('sub_menu_default'))->toBe('show');
});

test('nilai sub menu default tidak valid ditolak', function () {
    DataPokok::create(['singleton_key' => 1, 'nama_aplikasi' => 'Test']);

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.data-pokok.form')
        ->set('form.nama_aplikasi', 'Test')
        ->set('form.sub_menu_default', 'mungkin')
        ->call('save')
        ->assertHasErrors(['form.sub_menu_default']);
});
