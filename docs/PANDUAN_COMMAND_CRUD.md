# Panduan Command CRUD Reusable

Command `make:crud` membuat struktur CRUD untuk aplikasi utama atau module `nwidart/laravel-modules`. Tanpa opsi CLI, command berjalan interaktif dan menanyakan setiap fitur.

```bash
php artisan make:crud
```

Pertanyaan yang tersedia:

1. nama model tunggal;
2. target aplikasi utama atau module;
3. module tujuan;
4. field dan tipe data;
5. soft delete dan menu Sampah;
6. export CSV, Excel, dan cetak PDF;
7. import Excel/CSV;
8. checkbox dan bulk action;
9. permission granular;
10. route;
11. test Pest.
12. audit log aktivitas model.

Format field:

```text
name:string:unique,description:text:nullable,price:decimal,stock:integer,active:boolean
```

Tipe yang didukung adalah `string`, `text`, `integer`, `bigInteger`, `boolean`, `date`, `dateTime`, dan `decimal`. Modifier yang didukung adalah `nullable` dan `unique`.

## Mode non-interaktif

Target module dengan seluruh fitur aktif:

```bash
php artisan make:crud Product \
    --module=Inventory \
    --fields="name:string:unique,price:decimal,stock:integer,active:boolean" \
    --soft-delete --export --import --bulk --permissions --route --test \
    --audit \
    --no-interaction
```

Target aplikasi dengan beberapa fitur dimatikan:

```bash
php artisan make:crud Note \
    --app \
    --fields="title:string,content:text:nullable" \
    --no-soft-delete --no-export --no-import --no-bulk --no-permissions \
    --route --test --no-interaction
```

Jika mode non-interaktif tidak memberikan pilihan fitur, default-nya seluruh fitur aktif. Gunakan opsi `--no-*` untuk mematikannya.

Import aktif secara default: komponen hasil generate memakai `WithCrudImport`, `crudImportRules()` diisi dari definisi field (termasuk `unique`), toolbar menampilkan tombol Import, dan permission `{resource}.import` ditambahkan. Matikan dengan `--no-import`.

Audit aktif secara default. Gunakan `--audit` untuk eksplisit mengaktifkannya atau `--no-audit` bila suatu model memang tidak boleh dicatat.

## Lokasi hasil untuk module

Model, policy, controller, migration, wrapper view, route, permission, dan test dibuat di dalam `Modules/{Module}`. Komponen Livewire Volt dibuat di `resources/views/components/{module}/{resource}` agar mengikuti discovery Livewire proyek ini.

Permission hasil generator dimuat otomatis dari:

```text
config/permissions/*.php
Modules/*/config/permissions/*.php
```

Permission target aplikasi memakai format `products.view`. Permission target module memakai prefix module untuk mencegah bentrok, misalnya `inventory.products.view`.

Setelah generator selesai, periksa hasilnya lalu jalankan:

```bash
php artisan migrate
php artisan db:seed --class=PermissionSeeder
```

Gunakan `--force` hanya ketika memang ingin menimpa seluruh file CRUD dengan nama yang sama.
