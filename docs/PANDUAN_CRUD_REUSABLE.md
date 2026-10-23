# Panduan CRUD Reusable

Fondasi CRUD reusable terdiri dari trait Livewire `WithCrudTable`, layanan `CsvExporter`, dan komponen Blade pada `resources/views/components/crud`. CRUD User adalah implementasi referensi yang sudah lengkap.

## Fitur bawaan

- pencarian dengan debounce;
- pagination dan pilihan jumlah data per halaman;
- sorting kolom dengan whitelist;
- export CSV dan Excel (`.xlsx`) untuk seluruh hasil filter atau hanya data terpilih;
- import Excel (`.xlsx`) dan CSV dengan template, validasi per baris, dan pembatalan total bila ada baris salah (lihat [Import Excel/CSV](#import-excelcsv));
- proteksi CSV formula injection;
- soft delete, daftar sampah, restore, dan hapus permanen;
- bulk delete, bulk restore, dan bulk force delete;
- konfirmasi penghapusan menggunakan SweetAlert;
- loading state, empty state, reset filter, serta otorisasi setiap aksi.
- primary key integer maupun UUID.
- audit log otomatis untuk create, update, delete, restore, force delete, bulk action, dan export.

## Membuat CRUD baru

Gunakan trait pada komponen Livewire dan implementasikan kontraknya.

```php
use App\Livewire\Concerns\WithCrudTable;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;

new class extends Component
{
    use WithCrudTable;

    protected function crudModelClass(): string
    {
        return Category::class;
    }

    protected function crudFilteredQuery(): Builder
    {
        $query = Category::query()->search($this->search);

        return $this->applySorting($this->applyTrashMode($query));
    }

    protected function crudSortableFields(): array
    {
        return ['name', 'created_at'];
    }

    protected function crudExportPermission(): string
    {
        return 'categories.export';
    }

    protected function crudExportColumns(): array
    {
        return [
            'Nama' => 'name',
            'Dibuat' => 'created_at',
        ];
    }

    #[Computed]
    public function items()
    {
        return $this->crudFilteredQuery()->paginate($this->perPage);
    }
};
```

Urutan `applyTrashMode()` lalu `applySorting()` harus dipertahankan. Kolom dari request tidak pernah langsung dipakai untuk sorting; daftarkan hanya kolom yang aman di `crudSortableFields()`.

## Komponen Blade

```blade
<x-crud.toolbar export-permission="categories.export" />

<x-crud.bulk-actions
    :selected="$selected"
    :trash-mode="$trashMode"
    delete-permission="categories.delete"
    restore-permission="categories.restore"
    force-delete-permission="categories.force-delete"
/>

<x-crud.select-page :ids="$this->items->pluck('id')" :selected="$selected" />
<x-crud.row-checkbox :id="$item->id" />
<x-crud.sortable-header field="name" label="Nama" :sort-field="$sortField" :sort-direction="$sortDirection" />
<x-crud.empty-state colspan="5" message="Data tidak ditemukan." />
```

Komponen mengikuti nama properti standar: `search`, `perPage`, `sortField`, `sortDirection`, `trashMode`, dan `selected`. Hindari mengganti nama properti tersebut agar seluruh komponen tetap plug-and-play.

## Import Excel/CSV

Import bersifat opt-in. Tambahkan trait `WithCrudImport` di samping `WithCrudTable`, lalu definisikan aturan validasi per baris:

```php
use App\Livewire\Concerns\WithCrudImport;
use App\Livewire\Concerns\WithCrudTable;

new class extends Component
{
    use WithCrudImport;
    use WithCrudTable;

    protected function crudImportPermission(): string
    {
        return 'categories.import';
    }

    protected function crudImportRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'active' => ['nullable', 'boolean'],
        ];
    }
};
```

Lalu aktifkan tombolnya di toolbar:

```blade
<x-crud.toolbar :show-import="true" import-permission="categories.import" :import-errors="$importErrors" />
```

Perilaku:

- Tombol **Import** membuka modal: unduh template, pilih file `.xlsx`/`.csv` (maks. 5 MB), lalu proses.
- Template berisi judul kolom dari `crudImportColumns()`. Default-nya kolom `crudExportColumns()` yang berupa atribut dan punya aturan di `crudImportRules()`.
- Judul kolom dicocokkan dengan label atau nama atribut, tanpa membedakan huruf besar/kecil. Urutan kolom bebas.
- **Semua baris divalidasi dulu.** Satu baris salah berarti tidak ada data yang disimpan, dan modal menampilkan pesan seperti `Baris 3: Nama wajib diisi.`
- Kolom dengan aturan `unique` juga dicek duplikatnya di dalam file.
- Nilai `ya/tidak`, `true/false`, `1/0` dikonversi untuk aturan `boolean`. Tanggal serial Excel dikonversi untuk aturan `date`.
- Penyimpanan berjalan dalam satu transaksi database. Audit mencatat event `created` per baris dalam satu batch, ditambah satu event `imported`.

Hook opsional:

| Method | Default | Kegunaan |
| --- | --- | --- |
| `crudImportPermission()` | `''` (Gate `create` pada model) | Permission khusus import. |
| `crudImportColumns()` | Turunan dari kolom export | Ubah label/kolom template. |
| `crudImportMaxRows()` | `1000` | Batas baris per file. |
| `crudImportRow(array $data)` | `Model::create($data)` | Isi kolom tambahan, relasi, atau nilai default per baris. |

Pembaca file (`SpreadsheetReader`) dan prosesnya (`CrudImporter`) tidak memakai library tambahan; cukup ekstensi PHP `zip`. Hanya sheet pertama yang dibaca.

## Soft delete

Tambahkan trait pada model:

```php
use Illuminate\Database\Eloquent\SoftDeletes;

use SoftDeletes;
```

Tambahkan kolom melalui migration:

```php
Schema::table('categories', function (Blueprint $table): void {
    $table->softDeletes();
});
```

Policy model harus memiliki kemampuan `delete`, `restore`, dan `forceDelete`. Tambahkan juga permission `export`, `restore`, dan `force-delete` ke `config/permissions.php`, kemudian jalankan:

```bash
php artisan db:seed --class=PermissionSeeder
```

## Aturan keamanan

- Setiap aksi tunggal maupun massal tetap menjalankan policy untuk setiap model.
- Override `crudDeletionBlockReason()` untuk aturan bisnis, misalnya mencegah penghapusan akun sendiri atau record sistem terakhir.
- Override `crudBeforeForceDelete()` untuk melepas pivot atau membersihkan relasi sebelum hapus permanen.
- Bulk action hanya tersedia pada filter `Data aktif` atau `Sampah`; mode `Semua data` sengaja tidak menjalankan aksi massal campuran.
- Hapus permanen tidak dapat dibatalkan dan harus menggunakan permission terpisah.
- Export hanya memuat hasil filter aktif. Jika ada pilihan baris, export hanya memuat ID terpilih.
- Export Excel memakai format XLSX asli, freeze header, auto-filter, dan tipe angka tanpa dependency tambahan.

## Checklist modul baru

1. Model memakai `SoftDeletes` dan tabel mempunyai `deleted_at`.
2. Permission view/create/update/delete/export/restore/force-delete terdaftar.
3. Policy memeriksa setiap permission.
4. Query pencarian dikelompokkan agar tidak melewati filter lain.
5. Kolom sorting memakai whitelist.
6. Aksi hapus permanen membersihkan relasi yang tidak memiliki cascade.
7. Test mencakup akses, soft delete, restore, force delete, bulk action, dan export.
8. Aktifkan trait `Auditable` agar perubahan menjadi bukti aktivitas sistem.
