<?php

use Modules\Data\Models\Siswa;

test('model Siswa dapat menyimpan data', function () {
    $model = Siswa::query()->create([
        'nama' => 'Contoh nama',
        'nis' => 'Contoh nis',
        'alamat' => 'Contoh alamat',
    ]);

    expect($model->exists)->toBeTrue();
    $model->delete();
    expect(Siswa::withTrashed()->find($model->getKey()))->not->toBeNull();
});
