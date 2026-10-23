<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Superadmin\Models\Sistemkategori;

uses(RefreshDatabase::class);

test('authenticated users can search registered sources', function () {
    $user = User::factory()->create([
        'name' => 'Budi Santoso',
        'email' => 'budi@example.test',
    ]);
    Sistemkategori::create([
        'nama' => 'Keuangan',
        'keterangan' => 'Pengaturan keuangan aplikasi',
        'is_status' => true,
    ]);

    $response = $this->actingAs($user)->getJson(route('global-search.index', ['q' => 'Keuangan']));

    $response
        ->assertOk()
        ->assertJsonPath('query', 'Keuangan')
        ->assertJsonFragment([
            'type' => 'Kategori Sistem',
            'title' => 'Keuangan',
        ]);
});

test('recent search terms are stored per session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('global-search.remember'), ['q' => 'pengguna aktif'])
        ->assertOk();

    $this->getJson(route('global-search.index'))
        ->assertOk()
        ->assertJsonPath('recent.0', 'pengguna aktif');
});

test('global search requires authentication', function () {
    $this->getJson(route('global-search.index', ['q' => 'data']))
        ->assertUnauthorized();
});

test('authenticated users can open the complete results page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('global-search.results', ['q' => 'data']))
        ->assertOk()
        ->assertSee('Hasil Pencarian');
});
