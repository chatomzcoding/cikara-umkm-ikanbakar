<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Superadmin\Models\DataPokok;

beforeEach(function () {
    $role = Role::create([
        'name' => 'superadmin',
        'display_name' => 'Super Admin',
    ]);

    $this->superadmin = User::factory()->create();
    $this->superadmin->addRole($role);
    $this->actingAs($this->superadmin);
});

test('halaman data pokok dapat diakses oleh superadmin terverifikasi', function () {
    $this->actingAs($this->superadmin)
        ->get(route('superadmin.sistem.data-pokok.index'))
        ->assertSuccessful()
        ->assertSee('Data Pokok');
});

test('favicon data pokok digunakan sebagai favicon sistem', function () {
    Storage::fake('public');
    Storage::disk('public')->put('data-pokok/favicon/website.png', 'favicon');

    DataPokok::create([
        'nama_aplikasi' => 'Linqo Starter',
        'favicon' => 'data-pokok/favicon/website.png',
    ]);

    $this->actingAs($this->superadmin)
        ->get(route('superadmin.sistem.data-pokok.index'))
        ->assertSuccessful()
        ->assertSee('type="image/png"', false)
        ->assertSee(Storage::disk('public')->url('data-pokok/favicon/website.png'), false);
});

test('form kosong ketika data pokok belum tersedia', function () {
    Livewire::test('superadmin.sistem.data-pokok.form')
        ->assertSet('id', null)
        ->assertSet('form.nama_aplikasi', null)
        ->assertSet('form.email', null)
        ->assertSet('form.url_website', null);
});

test('data pokok dapat ditambahkan dan diperbarui melalui livewire', function () {
    Storage::fake('public');

    $component = Livewire::test('superadmin.sistem.data-pokok.form')
        ->set('form.nama_aplikasi', 'Linqo Starter')
        ->set('form.keterangan', 'Template aplikasi utama')
        ->set('form.no_telp', '+62 812 3456 7890')
        ->set('form.email', 'admin@linqo.test')
        ->set('form.nama_owner', 'Linqo')
        ->set('form.alamat', 'Jakarta')
        ->set('form.tanggal_dibuat', '2026-08-04')
        ->set('form.url_website', 'https://linqo.test')
        ->set('logo', UploadedFile::fake()->image('logo.png', 300, 100))
        ->set('favicon', UploadedFile::fake()->image('favicon.png', 32, 32))
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify')
        ->assertDispatched('favicon-updated')
        ->assertDispatched('handle-form');

    $item = DataPokok::sole();

    expect($item->uuid)->not->toBeEmpty()
        ->and($item->nama_aplikasi)->toBe('Linqo Starter')
        ->and($item->tanggal_dibuat->format('Y-m-d'))->toBe('2026-08-04');

    Storage::disk('public')->assertExists($item->logo);
    Storage::disk('public')->assertExists($item->favicon);

    $component
        ->set('form.nama_aplikasi', 'Linqo Template')
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh()->nama_aplikasi)->toBe('Linqo Template');

    expect(DataPokok::count())->toBe(1);
});

test('input wajib dan format kontak divalidasi', function () {
    Livewire::test('superadmin.sistem.data-pokok.form')
        ->set('form.nama_aplikasi', '')
        ->set('form.email', 'bukan-email')
        ->set('form.no_telp', 'telepon')
        ->set('form.url_website', 'bukan-url')
        ->call('save')
        ->assertHasErrors([
            'form.nama_aplikasi' => 'required',
            'form.email' => 'email',
            'form.no_telp' => 'regex',
            'form.url_website' => 'url',
        ]);
});

test('form memuat dan memperbarui data yang sudah tersedia tanpa membuat record baru', function () {
    $item = DataPokok::create([
        'nama_aplikasi' => 'Linqo Starter',
        'email' => 'admin@linqo.test',
    ]);

    Livewire::test('superadmin.sistem.data-pokok.form')
        ->assertSet('id', $item->id)
        ->assertSet('form.nama_aplikasi', 'Linqo Starter')
        ->assertSet('form.email', 'admin@linqo.test')
        ->set('form.nama_aplikasi', 'Linqo Utama')
        ->call('save')
        ->assertHasNoErrors();

    expect(DataPokok::count())->toBe(1)
        ->and($item->fresh()->nama_aplikasi)->toBe('Linqo Utama');
});
