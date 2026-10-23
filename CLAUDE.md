# CLAUDE.md

Panduan kerja untuk Claude Code pada repo ini. Bahasa kode, komentar, UI, dan dokumentasi: **Indonesia**.

## Apa ini

**Linqo Starter** — template/boilerplate admin panel yang disalin sebagai fondasi proyek lain. Nilainya ada pada fondasi reusable, bukan fitur bisnisnya. Utamakan solusi generik (trait, service, stub generator, config-driven) daripada hardcode untuk satu kasus.

Stack: Laravel 12 / PHP 8.2+ · Livewire 4 · Flux 2 · Fortify (auth + 2FA) · Laratrust 8 (RBAC) · nwidart/laravel-modules 12 · dompdf · Tailwind 4 + Vite 7 · Pest 4 + Pint · tema admin **Lineone**.

## Perintah

```bash
composer dev          # serve + queue:listen + vite (concurrently)
composer test         # config:clear + pint --test + artisan test
composer lint         # pint --parallel (jalankan sebelum commit)
php artisan test tests/Feature/WithCrudTableTest.php

composer lock:check   # validasi composer.lock sinkron dengan composer.json (juga dijalankan di composer test)
composer update --lock --no-scripts   # setelah mengubah composer.json / Modules/*/composer.json, lalu commit composer.lock

php artisan make:crud                      # generator CRUD, interaktif
php artisan migrate
php artisan db:seed --class=PermissionSeeder   # WAJIB setelah menambah permission
```

### Deploy

- Di server: `composer install --no-dev --optimize-autoloader --no-interaction`. **Jangan** `composer update` di server.
- `composer.lock` harus ikut di-commit setiap kali `composer.json` atau `Modules/*/composer.json` berubah. Root memakai `wikimedia/composer-merge-plugin`, jadi file module ikut digabung ke root.
- Sebelum push: `composer lock:check` harus lolos.

## Alur request

```
routes/web.php + Modules/*/routes/web.php
   ↓ middleware global: AssignRequestId, EnsureUserIsActive
   ↓ group: auth → verified → can:{resource}.view
Controller (tipis — hanya return view)
   ↓
Blade wrapper: <x-lineone menu="master"><x-header title="..."/><livewire:... /></x-lineone>
   ↓
Livewire single-file component (⚡)
   ↓
trait WithCrudTable → Gate/Policy → Model → AuditLogger
```

## Konvensi Livewire 4 (`⚡`) — penting

Komponen Livewire adalah **file tunggal berawalan `⚡`** di `resources/views/components/`. Nama komponen mengikuti path folder:

| File | Dipanggil sebagai |
| --- | --- |
| `components/superadmin/sistem/user/⚡index.blade.php` | `<livewire:superadmin.sistem.user.index />` |
| `components/app/siswas/⚡form.blade.php` | `<livewire:app.siswas.form />` |

**Komponen Livewire milik module tetap ditaruh di `resources/views/components/{module-alias}/`, bukan di dalam `Modules/`** — keterbatasan discovery Livewire. Yang masuk `Modules/` hanya Model, Policy, Controller, migration, route, config permission, test, dan wrapper view. Jangan "merapikan" ini dengan memindahkannya.

Blade `@props` component biasa (non-Livewire) ada di `components/crud/*` dan tidak berawalan `⚡`.

## Pilar reusable

### 1. `WithCrudTable` — jantung template

`app/Livewire/Concerns/WithCrudTable.php`. Satu trait memberi search debounce, pagination, sorting whitelist, trash mode, bulk action, export CSV/XLSX/PDF, dan audit otomatis.

5 method wajib di-implement: `crudModelClass()`, `crudFilteredQuery()`, `crudSortableFields()`, `crudExportColumns()`, `crudExportPermission()`.

Aturan yang tidak boleh dilanggar:

- Di `crudFilteredQuery()`, urutannya **`applySorting($this->applyTrashMode($query))`** — trash mode dulu, baru sorting.
- Kolom sorting **hanya** dari whitelist `crudSortableFields()`. Kolom dari request tidak pernah langsung dipakai (`abort 422` kalau tak terdaftar).
- Nama properti standar — `search`, `perPage`, `sortField`, `sortDirection`, `trashMode`, `selected` — jangan diganti, komponen `x-crud.*` bergantung padanya.
- Setiap aksi tunggal **dan setiap item** bulk action tetap lewat `Gate::authorize()`. Jangan buat shortcut.
- Model tanpa soft delete: override `crudUsesSoftDeletes(): false`.

Hook opsional: `crudDeletionBlockReason()` (aturan bisnis, mis. cegah hapus diri sendiri), `crudBeforeForceDelete()` (lepas pivot/relasi), `crudPdf*()`, `crudExcel*()`.

Referensi implementasi terlengkap: `components/superadmin/sistem/user/⚡index.blade.php`.

**Import Excel/CSV** adalah trait terpisah dan opt-in: `App\Livewire\Concerns\WithCrudImport` (dipakai bersama `WithCrudTable`). Wajib `crudImportRules()` (kunci = atribut model); hook opsional `crudImportPermission()`, `crudImportColumns()`, `crudImportMaxRows()`, `crudImportRow()`. Toolbar: `<x-crud.toolbar :show-import="true" import-permission="..." :import-errors="$importErrors" />`. Aturan yang tidak boleh dilanggar:

- Import **all-or-nothing**: semua baris divalidasi dulu (`App\Support\Crud\CrudImporter`), satu baris salah = tidak ada yang disimpan. Jangan ubah jadi simpan sebagian tanpa keputusan eksplisit.
- Otorisasi di setiap aksi (`importExcel`, `downloadImportTemplate`), bukan hanya di tombol.
- Pembaca file `SpreadsheetReader` tanpa library tambahan. Dua jebakan SimpleXML yang sudah diperbaiki: elemen kosong bernilai `false` (bandingkan dengan `null`), dan atribut sel harus dibaca lewat `->attributes()` setelah `children($namespace)`.
- Tanggal serial Excel dihitung di UTC; zona `Asia/Jakarta` tahun 1899 punya offset LMT yang menggeser hasil.

Contoh referensi: `components/data/siswas/⚡index.blade.php`. Panduan lengkap: `docs/PANDUAN_CRUD_REUSABLE.md`.

### 2. Generator `make:crud`

`app/Console/Commands/MakeCrudCommand.php` + `app/Support/Crud/CrudGenerator.php` + `stubs/crud/`.

```bash
php artisan make:crud Product --module=Inventory \
    --fields="name:string:unique,price:decimal,stock:integer,active:boolean" \
    --soft-delete --export --import --bulk --permissions --route --test --audit --no-interaction
```

Tipe field: `string`, `text`, `integer`, `bigInteger`, `boolean`, `date`, `dateTime`, `decimal`. Modifier: `nullable`, `unique`. Semua fitur default aktif; matikan dengan `--no-*`.

**Kalau mengubah bentuk output CRUD, ubah stub-nya di `stubs/crud/`, bukan hanya file hasil generate** — kalau tidak, proyek turunan tidak ikut mendapat perbaikannya. Token stub berformat `%%NAMA_TOKEN%%`, didefinisikan di `CrudGenerator::tokens()`.

### 3. Permission (Laratrust)

Format nama: **`resource.action`**, huruf kecil, kebab-case — `products.view`, `users.assign-roles`. Hindari `products.manage`.

Katalog permission dimuat dari tiga sumber, digabung saat boot oleh `AppServiceProvider::registerGeneratedPermissions()`:

```
config/permissions.php                 (inti)
config/permissions/*.php               (hasil generator, target app)
Modules/*/config/permissions/*.php     (hasil generator, target module)
```

Permission module diberi prefix alias module agar tidak bentrok: `inventory.products.view`.

Penegakan berlapis — semuanya wajib, bukan pilihan:

1. Route: `->middleware('can:products.view')`
2. Livewire: `Gate::authorize('viewAny', Product::class)` di `mount()` **dan** di setiap action. Request Livewire berikutnya tidak dilindungi middleware route halaman awal.
3. Blade: `@can` / `@canany` — hanya kenyamanan UI, bukan pengamanan.

Policy pakai trait `App\Policies\Concerns\ChecksPermissions` (`$this->allowed($user, 'products.view')`). Daftarkan policy app di `AppServiceProvider`, policy module di service provider module-nya.

`Gate::before` memberi bypass penuh ke role `superadmin`. Role tersebut dilindungi: tidak bisa diedit/dihapus, dan role-nya tidak bisa dilepas dari superadmin terakhir.

Setelah menambah permission: **`php artisan db:seed --class=PermissionSeeder`** (idempotent, pakai `updateOrCreate`).

Role non-superadmin dengan permission default didefinisikan di `config/permissions.php` → `role_defaults`. `PermissionSeeder` membuat role-nya dan **menambahkan** permission default lewat `syncWithoutDetaching` (tidak menghapus perubahan manual dari UI). Saat ini role `admin` hanya mendapat `data-pokok.view`, `audit-logs.view`, `users.view` — menu Master. Permission `error-pages.*`, `categories.*`, dan `roles.*` **khusus superadmin**: jangan dimasukkan ke `role_defaults`.

### 4. Audit log

Tempel trait `App\Models\Concerns\Auditable` pada model → otomatis mencatat created/updated/soft_deleted/restored/force_deleted. Field sensitif di-redact via `config/audit.php`.

Manual:

```php
app(AuditLogger::class)->record(
    event: 'approved',
    description: 'Pengajuan disetujui',
    subject: $application,
    oldValues: ['status' => 'pending'],
    newValues: ['status' => 'approved'],
    metadata: ['note' => 'Dokumen lengkap'],
);
```

Bulk action dibungkus `runInBatch()` agar satu batch berbagi `batch_uuid`.

### 5. Layanan pendukung

| Service | Catatan |
| --- | --- |
| `NotificationService` | `alert()` / `event()` / `activity()` / `log()` — `(recipients, title, message, options)`. `event()` di sini kategori tampilan, bukan Laravel Event. |
| `GlobalSearchService` | Sumber didaftarkan deklaratif di `config/global-search.php`; placeholder `{name}`, `{relasi.field}`. Tambah sumber = tambah config, bukan kode. |
| `UserLifecycleService` | `suspend()` / `deactivate()` (wajib `$reason`) / `activate()`. Enum `App\Enums\UserStatus`. Ditegakkan middleware `EnsureUserIsActive`. |
| `UserImpersonationService` | Permission `users.impersonate`. |
| `ErrorPageManager` | Halaman 403/404/419/429/500/503 dapat diedit dari UI, punya fallback saat DB tak terbaca. |
| Sub menu sidebar | Default tampilan (`hide` / `show`) diatur di Data Pokok (kolom `sub_menu_default`). `Lineone::render()` membacanya dan menambah kelas `is-sidebar-open` di body saat `show`; `hide` memakai CSS di `components/lineone.blade.php` agar panel tertutup. Tombol garis (`sidebar-toggle`) tetap membuka/menutup. |

### 6. Identitas publik ULID

Setiap tabel yang dibuat lewat migration punya kolom **`ulid`** (unik, terisi otomatis saat record dibuat). Kolom ini dipakai sebagai identitas di URL/route, menggantikan `id` auto-increment, supaya URL tidak mudah ditebak dan tidak membocorkan jumlah/urutan data.

- Migration: `$table->ulid('ulid')->unique();` — wajib ada di setiap tabel domain.
- Model: memakai trait generik (mis. `App\Models\Concerns\HasPublicUlid`, dibungkus `HasUlids` bawaan Laravel) yang mengisi `ulid` saat `creating` dan meng-override `getRouteKeyName()` menjadi `'ulid'`, sehingga route model binding `{ulid}` otomatis memakai kolom ini.
- Route: parameter memakai `{ulid}`, bukan `{id}` / `{model}` yang di-resolve dengan `id`. Contoh: `/data/siswas/{ulid}`.
- `id` tetap dipakai sebagai primary key internal (relasi, pivot, foreign key). Jangan pernah mengekspos `id` ke URL, JSON API, atau nama file export.
- Query/whitelist sorting dan pencarian tidak memakai `ulid` sebagai kolom sorting default; `ulid` tidak ikut `$fillable` (diisi otomatis).
- Tabel lama tanpa `ulid` harus diberi migration tambahan yang mengisi `ulid` untuk baris yang sudah ada, lalu dijadikan NOT NULL dan unique. Contoh: `Modules/Data/database/migrations/2026_10_03_000000_add_ulid_to_siswas_table.php`.

Implementasi generator:
- `make:crud` selalu menyisipkan kolom `ulid` (`stubs/crud/migration.stub`) dan trait `HasPublicUlid` (`app/Models/Concerns/HasPublicUlid.php`) ke model (`stubs/crud/model.stub`). Tidak ada flag `--no-ulid`.
- Batas saat ini: payload Livewire (`buttonEdit`, `deleteItem`, `selected`, `wire:key`, `findCrudModel`) **masih memakai primary key `id`**, karena `WithCrudTable` juga dipakai tabel lama tanpa `ulid` (mis. user management). Identitas di URL/route/route model binding memakai `ulid`; payload Livewire tidak pernah berupa URL. Memindahkan payload Livewire ke `ulid` perlu dikerjakan sebagai perubahan terpisah di `WithCrudTable` dan `x-crud.*`.
- Tabel framework (mis. `users`) dibuat di luar `make:crud` dan belum punya `ulid`.

### 7. Halaman auth (Fortify)

Semua view Fortify ada di `resources/views/pages/auth/` (login, register, forgot-password, reset-password, confirm-password, verify-email, two-factor-challenge) dan memakai komponen bersama:

- `<x-auth.shell title="..." description="..." page-title="...">` — kerangka Lineone (logo & nama dari Data Pokok, ilustrasi, pesan `status` dan error validasi, script toggle password). Ubah tampilan semua halaman auth di sini, bukan per halaman.
- `<x-auth.input name="..." type="..." icon="user|email|lock|key" />` — input berikon; `type="password"` otomatis mendapat tombol mata show/hide.
- `<x-auth.button>` — tombol submit utama.

Fitur auth dinyalakan/dimatikan lewat `config/fortify.php` → `features` (mis. hapus `Features::registration()` untuk menutup pendaftaran publik). Teks status Fortify (mis. `verification-link-sent`) diterjemahkan di `x-auth.shell`.

Pengaturan akun: `pengaturan-user` (`user.settings`, Livewire `user-settings`: profil + ganti password) dan `settings/two-factor` (`two-factor.show`, Livewire `settings.two-factor`: aktifkan/konfirmasi/nonaktifkan 2FA, kode pemulihan). Route 2FA dilindungi `password.confirm`, dan **setiap aksi Livewire 2FA mengecek ulang** `auth.password_confirmed_at` karena request Livewire tidak melewati middleware route.

## Menambah fitur baru — checklist

1. Model: tambahkan `SoftDeletes` + `Auditable` + trait ULID; migration punya `deleted_at` dan kolom `ulid`.
2. Permission `view/create/update/delete/export/import/restore/force-delete` terdaftar di config.
3. Policy dibuat dan didaftarkan di service provider yang tepat (app vs module).
4. Route dilindungi `can:`.
5. Komponen Livewire `⚡` pakai `WithCrudTable`, `Gate::authorize()` di `mount()` dan tiap action.
6. Wrapper view `<x-lineone menu="..."><x-header .../><livewire:... /></x-lineone>`.
7. Query pencarian dibungkus closure agar tidak membocorkan filter lain.
8. Test Pest: akses ditolak/diizinkan, soft delete, restore, force delete, bulk, export, import.
9. `php artisan migrate && php artisan db:seed --class=PermissionSeeder`.
10. `composer lint` sebelum commit.

## Testing

Pest 4. `tests/Pest.php` memasang `RefreshDatabase` untuk `tests/Feature` **dan** `Modules/*/tests/Feature` (lewat `->in('Feature', '../Modules/*/tests/Feature')`); `phpunit.xml` juga mendaftarkan `Modules/*/tests/Feature` di testsuite `Feature`. Tanpa dua-duanya, test hasil generator di module tidak pernah tereksekusi oleh `composer test` — Pest hanya memuat satu `tests/Pest.php` di root, jadi jangan taruh `Pest.php` terpisah di dalam module. Test hasil generator masuk `tests/Feature/Generated/` (app) atau `Modules/{Module}/tests/Feature/` (module).

## Dokumentasi

`docs/PANDUAN_*.md` — permission, CRUD reusable, command CRUD, audit log, notifikasi, siklus hidup user, halaman error. **Perbarui panduan terkait setiap kali mengubah pilar reusable**; itu yang dibaca pengembang proyek turunan.

## Jebakan yang sudah diketahui

- Flux (`livewire/flux`) masih terpasang di `composer.json` tetapi **tidak dipakai lagi** di view mana pun. Jangan menulis UI baru dengan `<flux:*>`; pakai markup Lineone.
- `Siswa` / tabel `siswas` (model, policy, route, `config/permissions/siswas.php`, `components/app/siswas/`) adalah **demo hasil generator**, bukan domain nyata. Hapus saat memulai proyek turunan.
- Module `Data` (`Modules/Data`) adalah **contoh referensi CRUD berbasis module** — dibuat dengan `make:crud Siswa --module=Data` memakai tabel `siswas` yang sama dengan demo di atas (dua model beda namespace, satu tabel: `App\Models\Siswa` dan `Modules\Data\Models\Siswa`). Migration bawaan generator untuk `siswas` **sengaja dihapus** dari `Modules/Data/database/migrations` karena tabelnya sudah ada — kalau membuat module CRUD baru di atas tabel yang sudah ada, lakukan hal yang sama. Route `data.siswas.index` dipasang ke menu sidebar "Data" (`resources/views/layouts/menu-main.blade.php`). Scaffold bawaan `module:make` (`DataController`, `routes/api.php` resource kosong, view "Hello World") sudah dibuang karena tidak dipakai — jangan dikembalikan.
- Controller hanya berisi method yang terpasang di route. Saat `module:make` / `make:controller --resource` menghasilkan method scaffold (`create`, `store`, `edit`, ...) yang tidak dipakai, buang — jangan biarkan mengembalikan view yang tidak ada.
- `.env` masih `APP_NAME=Laravel` dan `APP_LOCALE=en` padahal UI berbahasa Indonesia. Nama & logo aplikasi yang tampil sebenarnya diambil dari singleton **Data Pokok** di database.
