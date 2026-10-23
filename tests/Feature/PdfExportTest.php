<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Crud\PdfExporter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Superadmin\Models\DataPokok;

beforeEach(function () {
    $this->superadminRole = Role::create([
        'name' => 'superadmin',
        'display_name' => 'Super Admin',
    ]);

    $this->superadmin = User::factory()->create();
    $this->superadmin->addRole($this->superadminRole);
});

test('cetak PDF tersedia dan mendukung data terpilih', function () {
    $target = User::factory()->create(['name' => 'Budi Santoso']);

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->set('selected', [$target->id])
        ->call('exportPdf')
        ->assertFileDownloaded();
});

test('cetak PDF ditolak ketika data melebihi batas baris', function () {
    config()->set('crud.pdf.max_rows', 1);

    User::factory()->count(3)->create();

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->call('exportPdf')
        ->assertNoFileDownloaded()
        ->assertDispatched('notify');
});

test('PdfExporter menghasilkan berkas PDF dari kolom string maupun closure', function () {
    User::factory()->create(['name' => 'Budi Santoso']);

    $pdf = app(PdfExporter::class)->make(
        User::query(),
        ['Nama' => fn (User $user) => strtoupper($user->name), 'Email' => 'email'],
        ['title' => 'Laporan Uji Coba', 'orientation' => 'landscape'],
    );

    expect($pdf->output())->toStartWith('%PDF-');
});

test('kop surat memakai identitas dan logo dari data pokok', function () {
    Storage::fake('public');
    Storage::disk('public')->put('logo/sekolah.png', base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
    ));

    DataPokok::create([
        'nama_aplikasi' => 'SMA Nusantara',
        'alamat' => 'Jl. Merdeka No. 1',
        'no_telp' => '021-000000',
        'email' => 'info@nusantara.test',
        'logo' => 'logo/sekolah.png',
    ]);

    User::factory()->create();

    $pdf = app(PdfExporter::class)->make(User::query(), ['Nama' => 'name'], ['title' => 'Daftar User']);

    expect($pdf->output())->toStartWith('%PDF-');
});

test('cetak PDF memakai view kustom bila disetel lewat config', function () {
    config()->set('crud.pdf.view', 'exports.pdf');

    User::factory()->create();

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->call('exportPdf')
        ->assertFileDownloaded();
});
