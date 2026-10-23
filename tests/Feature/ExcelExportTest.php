<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Crud\XlsxExporter;
use Livewire\Livewire;

beforeEach(function () {
    $role = Role::create(['name' => 'superadmin', 'display_name' => 'Super Admin']);
    $this->superadmin = User::factory()->create();
    $this->superadmin->addRole($role);
});

test('export Excel tersedia dan mendukung data terpilih', function () {
    $target = User::factory()->create(['name' => 'Budi Santoso']);

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->set('selected', [$target->id])
        ->call('exportExcel')
        ->assertFileDownloaded();
});

test('XlsxExporter menghasilkan workbook asli dan menyimpan formula sebagai teks', function () {
    $target = User::factory()->create(['name' => '=SUM(1,1)']);
    $path = app(XlsxExporter::class)->createTemporaryFile(
        User::query()->whereKey($target->id),
        ['ID' => 'id', 'Nama' => 'name', 'Email' => fn (User $user) => $user->email],
    );

    try {
        expect(file_get_contents($path, false, null, 0, 2))->toBe('PK');

        $zip = new ZipArchive;
        expect($zip->open($path))->toBeTrue();

        $requiredFiles = [
            '[Content_Types].xml',
            '_rels/.rels',
            'xl/workbook.xml',
            'xl/_rels/workbook.xml.rels',
            'xl/styles.xml',
            'xl/worksheets/sheet1.xml',
        ];

        foreach ($requiredFiles as $file) {
            $xml = $zip->getFromName($file);
            expect($xml)->not->toBeFalse()
                ->and(simplexml_load_string($xml))->not->toBeFalse();
        }

        $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        expect($worksheet)
            ->toContain('=SUM(1,1)', 't="inlineStr"', 't="n"', '<autoFilter')
            ->not->toContain('<f>');

        $zip->close();
    } finally {
        @unlink($path);
    }
});
