<?php

use App\Models\ErrorPageSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\ErrorPageManager;
use Database\Seeders\ErrorPageSettingSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(ErrorPageSettingSeeder::class);
    $role = Role::create(['name' => 'superadmin', 'display_name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->addRole($role);
});

test('seeder membuat seluruh pengaturan halaman error', function () {
    expect(ErrorPageSetting::query()->orderBy('status_code')->pluck('status_code')->all())
        ->toBe(ErrorPageManager::SUPPORTED_CODES);
});

test('super admin dapat mengubah halaman error secara dinamis', function () {
    Livewire::actingAs($this->admin)
        ->test('superadmin.sistem.error-page.index')
        ->call('selectCode', 404)
        ->set('form.title', 'Konten Tidak Ada')
        ->set('form.message', 'Silakan periksa kembali alamat yang dibuka.')
        ->set('form.accent', 'sky')
        ->set('form.button_label', 'Ke awal')
        ->set('form.button_url', '/')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $page = app(ErrorPageManager::class)->get(404);
    expect($page['title'])->toBe('Konten Tidak Ada')
        ->and($page['accent'])->toBe('sky');
});

test('url eksternal ditolak oleh editor', function () {
    Livewire::actingAs($this->admin)
        ->test('superadmin.sistem.error-page.index')
        ->set('form.button_url', 'https://example.com')
        ->call('save')
        ->assertHasErrors('form.button_url');
});

test('action toggle memperlihatkan perubahan status dengan jelas', function () {
    Livewire::actingAs($this->admin)
        ->test('superadmin.sistem.error-page.index')
        ->assertSet('form.is_active', true)
        ->call('toggleCustom')
        ->assertSet('form.is_active', false)
        ->assertSee('Nonaktif')
        ->call('toggleBackButton')
        ->assertSet('form.show_back_button', false)
        ->assertSee('Disembunyikan');
});

test('halaman 404 memakai pengaturan custom dan request id', function () {
    config(['app.debug' => false]);
    $setting = ErrorPageSetting::query()->where('status_code', 404)->firstOrFail();
    $setting->update(['title' => 'Halaman Khusus Tidak Ditemukan']);
    app(ErrorPageManager::class)->forget(404);

    $this->get('/alamat-yang-tidak-tersedia')
        ->assertNotFound()
        ->assertSee('Halaman Khusus Tidak Ditemukan')
        ->assertSee('Kode referensi:')
        ->assertHeader('X-Request-ID');
});

test('pengaturan nonaktif menggunakan konten fallback', function () {
    $setting = ErrorPageSetting::query()->where('status_code', 500)->firstOrFail();
    $setting->update(['is_active' => false, 'title' => 'Judul yang tidak dipakai']);
    app(ErrorPageManager::class)->forget(500);

    expect(app(ErrorPageManager::class)->get(500)['title'])
        ->toBe(ErrorPageManager::defaults()[500]['title']);
});

test('pengguna biasa tidak dapat membuka pengaturan halaman error', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('superadmin.sistem.error-page.index'))
        ->assertForbidden();
});

test('preview tersedia untuk super admin tanpa menghasilkan status error', function () {
    $this->actingAs($this->admin)
        ->get(route('superadmin.sistem.error-page.preview', 503))
        ->assertOk()
        ->assertSee('Error 503');
});
