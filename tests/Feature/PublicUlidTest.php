<?php

use Illuminate\Support\Str;
use Modules\Data\Models\Siswa;

test('ulid terisi otomatis saat record dibuat dan dipakai sebagai route key', function () {
    $siswa = Siswa::query()->create([
        'nama' => 'Budi',
        'nis' => '001',
        'alamat' => 'Jl. Contoh',
    ]);

    expect($siswa->ulid)->toBeString()->toHaveLength(26)
        ->and($siswa->getRouteKeyName())->toBe('ulid')
        ->and($siswa->getRouteKey())->toBe($siswa->ulid)
        ->and(Siswa::query()->where('ulid', $siswa->ulid)->first()?->is($siswa))->toBeTrue();
});

test('ulid yang sudah diisi tidak ditimpa', function () {
    $ulid = (string) Str::ulid();

    $siswa = new Siswa(['nama' => 'Ani', 'nis' => '002']);
    $siswa->ulid = $ulid;
    $siswa->save();

    expect($siswa->ulid)->toBe($ulid);
});
