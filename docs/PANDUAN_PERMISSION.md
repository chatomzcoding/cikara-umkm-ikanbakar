# Panduan Manajemen Role dan Permission

Panduan ini menjelaskan aturan dan cara menggunakan sistem authorization pada Linqo Starter. Implementasi menggunakan Laratrust, Laravel Gate/Policy, middleware route, dan pemeriksaan pada komponen Livewire.

## Konsep dasar

- **Permission** adalah satu kemampuan spesifik, misalnya `products.view` atau `products.delete`.
- **Role** adalah kumpulan permission, misalnya Operator, Editor, atau Auditor.
- **User** dapat memiliki satu atau beberapa role.
- Role `superadmin` adalah role sistem yang selalu memiliki seluruh akses dan tidak dapat diedit atau dihapus.

Alur akses sistem:

```text
User -> Role -> Permission -> Route/Policy/Action/UI
```

Permission harus diperiksa pada server. Menyembunyikan tombol di Blade hanya untuk kenyamanan pengguna dan tidak boleh menjadi satu-satunya pengamanan.

## Lokasi file utama

| Kebutuhan | Lokasi |
| --- | --- |
| Katalog permission | `config/permissions.php` |
| Seeder permission | `database/seeders/PermissionSeeder.php` |
| Policy aplikasi utama | `app/Policies` |
| Policy modul Superadmin | `Modules/Superadmin/app/Policies` |
| Registrasi Policy utama | `app/Providers/AppServiceProvider.php` |
| Registrasi Policy Superadmin | `Modules/Superadmin/app/Providers/SuperadminServiceProvider.php` |
| Route Superadmin | `Modules/Superadmin/routes/web.php` |
| UI Role dan Permission | `resources/views/components/superadmin/sistem/role/⚡index.blade.php` |
| Test permission | `tests/Feature/Superadmin/PermissionManagementTest.php` |

## Aturan penamaan permission

Gunakan format:

```text
resource.action
```

Nama menggunakan huruf kecil dan kebab-case jika terdiri dari beberapa kata.

Contoh yang benar:

```text
products.view
products.create
products.update
products.delete
products.export
purchase-orders.approve
users.assign-roles
roles.assign-permissions
```

Hindari nama yang terlalu umum atau bergantung pada label UI:

```text
manage
akses-menu
tombol-hapus
halaman-pertama
```

Gunakan action berikut jika sesuai:

| Action | Kegunaan |
| --- | --- |
| `view` | Melihat daftar dan detail data |
| `create` | Membuat data baru |
| `update` | Mengubah data |
| `delete` | Menghapus data |
| `restore` | Mengembalikan data soft delete |
| `export` | Mengunduh atau mengekspor data |
| `import` | Mengimpor data |
| `approve` | Menyetujui proses bisnis |
| `assign-*` | Menetapkan relasi sensitif seperti role atau permission |

Jangan menggunakan satu permission seperti `products.manage` jika operasi melihat, mengubah, dan menghapus perlu diberikan secara terpisah.

## Menambahkan permission baru

Tambahkan permission ke grup yang sesuai pada `config/permissions.php`.

Contoh permission laporan:

```php
'reports' => [
    'label' => 'Laporan',
    'permissions' => [
        'reports.view' => 'Melihat laporan',
        'reports.export' => 'Mengunduh laporan',
    ],
],
```

Kemudian jalankan:

```bash
php artisan db:seed --class=PermissionSeeder
```

Seeder menggunakan `updateOrCreate`, sehingga aman dijalankan berulang kali. Permission baru akan muncul pada halaman **Master > Role & Permission**.

Jangan membuat permission langsung melalui database atau Tinker tanpa menambahkannya ke konfigurasi. Permission yang tidak tercatat di konfigurasi tidak akan tampil konsisten pada matriks role.

## Mengatur permission melalui UI

1. Login menggunakan akun dengan akses pengelolaan role.
2. Buka **Master > Role & Permission**.
3. Klik **Tambah Role** atau **Ubah** pada role non-sistem.
4. Centang permission yang diperlukan.
5. Klik **Simpan**.
6. Buka **Master > User** untuk menetapkan role kepada pengguna.

Role yang mengelola role lain minimal membutuhkan:

```text
roles.view
roles.create
roles.update
roles.delete
roles.assign-permissions
```

Berikan hanya permission yang benar-benar diperlukan. Sebagai contoh, pengelola role tidak harus memperoleh `users.delete`.

## Melindungi route

Gunakan middleware `can` pada route:

```php
Route::get('reports', [ReportController::class, 'index'])
    ->middleware('can:reports.view')
    ->name('reports.index');

Route::get('reports/export', [ReportController::class, 'export'])
    ->middleware('can:reports.export')
    ->name('reports.export');
```

Route yang hanya memakai `auth` belum cukup untuk halaman administrasi. Minimal gunakan `auth`, `verified`, dan permission yang sesuai.

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('products', [ProductController::class, 'index'])
        ->middleware('can:products.view');
});
```

## Menggunakan Policy

Policy digunakan jika authorization berkaitan dengan model atau record tertentu.

```php
namespace Modules\Inventory\Policies;

use App\Models\User;
use Modules\Inventory\Models\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('superadmin')
            || $user->hasPermission('products.view');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasRole('superadmin')
            || $user->hasPermission('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole('superadmin')
            || $user->hasPermission('products.delete');
    }
}
```

Untuk mengurangi pengulangan, Policy dapat menggunakan trait `App\Policies\Concerns\ChecksPermissions`:

```php
use App\Policies\Concerns\ChecksPermissions;

class ProductPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'products.view');
    }

    public function update(User $user, Product $product): bool
    {
        return $this->allowed($user, 'products.update');
    }
}
```

Daftarkan Policy milik aplikasi utama di `AppServiceProvider`. Policy milik module harus didaftarkan pada service provider module agar aplikasi utama tidak bergantung pada module yang mungkin dinonaktifkan.

```php
Gate::policy(Product::class, ProductPolicy::class);
```

## Authorization pada Controller atau Livewire

Gunakan Gate sebelum membaca atau mengubah data sensitif.

```php
use Illuminate\Support\Facades\Gate;

public function mount(): void
{
    Gate::authorize('viewAny', Product::class);
}

public function edit(int $id): void
{
    $product = Product::findOrFail($id);
    Gate::authorize('update', $product);

    // Muat form setelah authorization berhasil.
}

public function delete(int $id): void
{
    $product = Product::findOrFail($id);
    Gate::authorize('delete', $product);

    $product->delete();
}
```

Untuk permission yang tidak membutuhkan model:

```php
Gate::authorize('reports.export');
```

Komponen Livewire harus melakukan pemeriksaan sendiri. Request Livewire berikutnya tidak boleh hanya mengandalkan middleware route halaman awal.

## Menampilkan atau menyembunyikan UI

Gunakan `@can` pada tombol dan menu:

```blade
@can('create', Modules\Inventory\Models\Product::class)
    <button wire:click="create">Tambah Produk</button>
@endcan

@can('update', $product)
    <button wire:click="edit({{ $product->id }})">Ubah</button>
@endcan

@can('products.export')
    <a href="{{ route('products.export') }}">Export</a>
@endcan
```

Untuk menu yang dapat dibuka oleh beberapa permission:

```blade
@canany(['products.view', 'categories.view', 'reports.view'])
    <a href="#">Master Data</a>
@endcanany
```

Tetap pasang authorization server-side meskipun tombol sudah disembunyikan.

## Menetapkan role kepada user

Assignment role menggunakan permission khusus:

```text
users.assign-roles
```

Contoh server-side:

```php
$user = User::findOrFail($id);
Gate::authorize('assignRoles', $user);

$user->roles()->sync($roleIds);
```

Memisahkan `users.update` dan `users.assign-roles` mencegah operator profil memberikan hak akses tinggi kepada pengguna lain.

## Role sistem `superadmin`

Aturan role `superadmin`:

- Selalu memperoleh akses penuh melalui Gate bypass.
- Menerima seluruh permission dari `PermissionSeeder`.
- Tidak dapat diedit.
- Tidak dapat dihapus.
- Role-nya tidak dapat dilepas dari superadmin terakhir.
- Akun yang sedang digunakan tidak dapat menghapus dirinya sendiri.

Jangan menghapus perlindungan ini hanya untuk mengubah tampilan permission. Buat role administrasi baru jika membutuhkan kombinasi akses yang berbeda.

## Mengubah nama permission

Mengganti key di konfigurasi tidak otomatis menghapus permission lama. Gunakan migration agar assignment role tetap terjaga.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('name', 'reports.download')
            ->update(['name' => 'reports.export']);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'reports.export')
            ->update(['name' => 'reports.download']);
    }
};
```

Setelah migration, sesuaikan `config/permissions.php`, Policy, route, Livewire, Blade, dan test.

## Menghapus permission

Sebelum menghapus permission:

1. Cari seluruh pemakaian permission di kode.
2. Hapus atau ganti pemeriksaan route, Policy, Livewire, dan Blade.
3. Hapus permission dari `config/permissions.php`.
4. Buat migration untuk menghapus record database.

Contoh migration:

```php
DB::table('permissions')
    ->where('name', 'reports.export')
    ->delete();
```

Relasi pada `permission_role` dan `permission_user` menggunakan foreign key cascade. Tetap lakukan backup dan verifikasi jika diterapkan pada data produksi.

## Testing permission

Setiap fitur baru minimal menguji:

- User tanpa permission menerima respons `403`.
- User dengan permission dapat membuka halaman.
- Permission `view` tidak otomatis mengizinkan `update` atau `delete`.
- Aksi Livewire ditolak walaupun dipanggil langsung.
- Tombol sensitif hanya muncul bagi user yang berwenang.
- Role sistem tetap terlindungi.

Contoh:

```php
test('viewer tidak dapat menghapus produk', function () {
    $permission = Permission::create(['name' => 'products.view']);
    $role = Role::create(['name' => 'viewer']);
    $role->givePermission($permission);

    $user = User::factory()->create();
    $user->addRole($role);
    $product = Product::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Product::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $product))->toBeFalse();
});
```

Jalankan test terkait:

```bash
php artisan test tests/Feature/Superadmin/PermissionManagementTest.php
```

## Checklist module atau fitur baru

- [ ] Tambahkan permission ke `config/permissions.php`.
- [ ] Jalankan `PermissionSeeder`.
- [ ] Buat Policy untuk model baru.
- [ ] Daftarkan Policy di service provider yang tepat.
- [ ] Lindungi route dengan middleware `can`.
- [ ] Lindungi setiap aksi Controller atau Livewire dengan Gate/Policy.
- [ ] Sembunyikan tombol dan menu dengan `@can` atau `@canany`.
- [ ] Pisahkan permission sensitif seperti approve, export, atau assign role.
- [ ] Tambahkan test akses diizinkan dan ditolak.
- [ ] Pastikan role `superadmin` tetap terlindungi.

## Pemecahan masalah

### Permission baru tidak muncul di UI

Jalankan:

```bash
php artisan db:seed --class=PermissionSeeder
php artisan optimize:clear
```

Pastikan permission sudah ditambahkan di `config/permissions.php`.

### User masih mendapatkan 403

Periksa hal berikut:

- Role sudah ditetapkan kepada user.
- Permission sudah dicentang pada role dan disimpan.
- Route menggunakan nama permission yang sama persis.
- Policy telah didaftarkan.
- User sudah logout dan login kembali jika session atau cache authorization masih lama.

### Tombol edit role tidak muncul

Pastikan user memiliki:

```text
roles.view
roles.update
roles.assign-permissions
```

Tombol edit memang tidak tersedia untuk role `superadmin` karena role tersebut dilindungi.
