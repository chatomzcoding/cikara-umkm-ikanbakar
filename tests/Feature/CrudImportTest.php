<?php

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Crud\CrudImporter;
use App\Support\Crud\SpreadsheetReader;
use App\Support\Crud\XlsxExporter;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Modules\Data\Models\Siswa;

beforeEach(function () {
    $role = Role::create(['name' => 'superadmin', 'display_name' => 'Super Admin']);
    $this->superadmin = User::factory()->create();
    $this->superadmin->addRole($role);
});

/** @param list<list<string|int|float|null>> $rows */
function makeXlsx(array $rows): string
{
    $strings = [];
    $sheetRows = '';

    foreach ($rows as $r => $row) {
        $cells = '';
        foreach ($row as $c => $value) {
            $ref = chr(65 + $c).($r + 1);
            if ($value === null) {
                continue;
            }
            if (is_string($value)) {
                $strings[] = htmlspecialchars($value, ENT_XML1);
                $cells .= "<c r=\"{$ref}\" t=\"s\"><v>".(count($strings) - 1).'</v></c>';
            } else {
                $cells .= "<c r=\"{$ref}\"><v>{$value}</v></c>";
            }
        }
        $sheetRows .= '<row r="'.($r + 1)."\">{$cells}</row>";
    }

    $ns = 'xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"';
    $path = tempnam(sys_get_temp_dir(), 'xlsx-test-').'.xlsx';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('xl/workbook.xml', "<?xml version=\"1.0\"?><workbook {$ns} xmlns:r=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships\"><sheets><sheet name=\"Data\" sheetId=\"1\" r:id=\"rId1\"/></sheets></workbook>");
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="worksheet" Target="worksheets/data.xml"/></Relationships>');
    $zip->addFromString('xl/worksheets/data.xml', "<?xml version=\"1.0\"?><worksheet {$ns}><sheetData>{$sheetRows}</sheetData></worksheet>");
    $zip->addFromString('xl/sharedStrings.xml', "<?xml version=\"1.0\"?><sst {$ns}>".implode('', array_map(fn ($s) => "<si><t>{$s}</t></si>", $strings)).'</sst>');
    $zip->close();

    return $path;
}

test('import csv menyimpan semua baris valid dan mencatat audit', function () {
    $csv = "\xEF\xBB\xBFNama;NIS;Alamat\nBudi;001;Jl. Mawar\nAni;002;\n";

    Livewire::actingAs($this->superadmin)
        ->test('data.siswas.index')
        ->set('importFile', UploadedFile::fake()->createWithContent('siswa.csv', $csv))
        ->call('importExcel')
        ->assertSet('importErrors', [])
        ->assertDispatched('crud-import-finished');

    expect(Siswa::query()->orderBy('nis')->pluck('nama')->all())->toBe(['Budi', 'Ani'])
        ->and(Siswa::query()->where('nis', '002')->value('alamat'))->toBeNull()
        ->and(Siswa::query()->whereNull('ulid')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('event', 'imported')->exists())->toBeTrue();
});

test('import xlsx membaca shared string dan urutan kolom bebas', function () {
    $path = makeXlsx([
        ['Alamat', 'Nama', 'Nis'],
        ['Jl. Melati', 'Citra', '003'],
        [null, 'Dodi', 4],
    ]);

    Livewire::actingAs($this->superadmin)
        ->test('data.siswas.index')
        ->set('importFile', UploadedFile::fake()->createWithContent('siswa.xlsx', file_get_contents($path)))
        ->call('importExcel')
        ->assertSet('importErrors', []);

    expect(Siswa::query()->where('nama', 'Citra')->value('alamat'))->toBe('Jl. Melati')
        ->and(Siswa::query()->where('nama', 'Dodi')->value('nis'))->toBe('4');

    @unlink($path);
});

test('satu baris tidak valid membatalkan seluruh import', function () {
    $csv = "Nama,Nis,Alamat\nBudi,001,\n,002,Jl. Kosong\n";

    $component = Livewire::actingAs($this->superadmin)
        ->test('data.siswas.index')
        ->set('importFile', UploadedFile::fake()->createWithContent('siswa.csv', $csv))
        ->call('importExcel')
        ->assertNotDispatched('crud-import-finished');

    expect(Siswa::query()->count())->toBe(0)
        ->and($component->get('importErrors'))->toHaveCount(1)
        ->and($component->get('importErrors')[0])->toStartWith('Baris 3:')->toContain('Nama');
});

test('header yang tidak sesuai ditolak dengan pesan jelas', function () {
    $component = Livewire::actingAs($this->superadmin)
        ->test('data.siswas.index')
        ->set('importFile', UploadedFile::fake()->createWithContent('siswa.csv', "Nama Lengkap,Nis\nBudi,001\n"))
        ->call('importExcel');

    expect($component->get('importErrors')[0])->toContain('Kolom tidak ditemukan', 'Nama', 'Alamat')
        ->and(Siswa::query()->count())->toBe(0);
});

test('file selain xlsx atau csv ditolak', function () {
    $component = Livewire::actingAs($this->superadmin)
        ->test('data.siswas.index')
        ->set('importFile', UploadedFile::fake()->create('siswa.pdf', 10, 'application/pdf'))
        ->call('importExcel');

    expect($component->get('importErrors'))->toBe(['File harus berformat .xlsx atau .csv.']);
});

test('halaman crud menampilkan tombol dan modal import', function () {
    $this->actingAs($this->superadmin)
        ->get(route('data.siswas.index'))
        ->assertOk()
        ->assertSee('Import Data')
        ->assertSee('Unduh Template Excel')
        ->assertSee('wire:click="importExcel"', false);
});

test('template import dapat diunduh dan berisi judul kolom', function () {
    Livewire::actingAs($this->superadmin)
        ->test('data.siswas.index')
        ->call('downloadImportTemplate')
        ->assertFileDownloaded('template-import-siswa.xlsx');
});

test('user tanpa permission import tidak bisa import maupun unduh template', function () {
    $role = Role::create(['name' => 'viewer', 'display_name' => 'Viewer']);
    $role->permissions()->attach(Permission::create(['name' => 'data.siswas.view'])->id);
    $user = User::factory()->create();
    $user->addRole($role);

    Livewire::actingAs($user)
        ->test('data.siswas.index')
        ->set('importFile', UploadedFile::fake()->createWithContent('siswa.csv', "Nama,Nis,Alamat\nBudi,001,\n"))
        ->call('importExcel')
        ->assertForbidden();

    Livewire::actingAs($user)
        ->test('data.siswas.index')
        ->call('downloadImportTemplate')
        ->assertForbidden();

    expect(Siswa::query()->count())->toBe(0);
});

test('file hasil export dapat diimport kembali', function () {
    Siswa::query()->create(['nama' => 'Eko', 'nis' => '010', 'alamat' => 'Jl. Anggrek']);
    $columns = ['Nama' => 'nama', 'Nis' => 'nis', 'Alamat' => 'alamat'];
    $path = app(XlsxExporter::class)->createTemporaryFile(Siswa::query(), $columns);
    Siswa::query()->forceDelete();

    $saved = [];
    $result = app(CrudImporter::class)->import(
        $path, 'xlsx', $columns,
        ['nama' => ['required'], 'nis' => ['required'], 'alamat' => ['nullable']],
        function (array $data) use (&$saved) {
            $saved[] = $data;
        },
        100,
    );
    @unlink($path);

    expect($result->failed())->toBeFalse()
        ->and($saved)->toBe([['nama' => 'Eko', 'nis' => '010', 'alamat' => 'Jl. Anggrek']]);
});

test('importer mengonversi tanggal serial excel dan nilai ya/tidak', function () {
    $path = makeXlsx([['Tanggal', 'Aktif'], [45658, 'Ya'], [45659.5, 'tidak']]);
    $saved = [];

    $result = app(CrudImporter::class)->import(
        $path, 'xlsx', ['Tanggal' => 'tanggal', 'Aktif' => 'aktif'],
        ['tanggal' => ['required', 'date'], 'aktif' => ['required', 'boolean']],
        function (array $data) use (&$saved) {
            $saved[] = $data;
        },
        100,
    );
    @unlink($path);

    expect($result->failed())->toBeFalse()
        ->and($saved)->toBe([
            ['tanggal' => '2025-01-01', 'aktif' => true],
            ['tanggal' => '2025-01-02 12:00:00', 'aktif' => false],
        ]);
});

test('importer menolak duplikat di dalam file untuk kolom unik dan batas jumlah baris', function () {
    $columns = ['Nis' => 'nis'];
    $rules = ['nis' => ['required', 'unique:siswas,nis']];
    $noop = fn () => null;

    $duplicate = makeXlsx([['Nis'], ['A1'], ['a1']]);
    $result = app(CrudImporter::class)->import($duplicate, 'xlsx', $columns, $rules, $noop, 100);
    expect($result->errors)->toBe(['Baris 3: Nis sama dengan baris 2.']);

    $tooMany = makeXlsx([['Nis'], ['A1'], ['A2'], ['A3']]);
    $result = app(CrudImporter::class)->import($tooMany, 'xlsx', $columns, $rules, $noop, 2);
    expect($result->errors[0])->toContain('melebihi batas 2 baris');

    @unlink($duplicate);
    @unlink($tooMany);
});

test('reader membaca csv dengan pemisah koma maupun titik koma', function () {
    $path = tempnam(sys_get_temp_dir(), 'csv-');
    file_put_contents($path, "a;b\n1;\"x;y\"\n");

    expect(app(SpreadsheetReader::class)->read($path, 'csv'))->toBe([['a', 'b'], ['1', 'x;y']]);

    @unlink($path);
});
