# Panduan Penggunaan Notifikasi

Panduan ini menjelaskan cara mengirim notifikasi ke dropdown Lineone melalui `App\Services\NotificationService`.

Notifikasi disimpan pada tabel `notifications` milik Laravel dan hanya ditampilkan kepada pengguna penerima. Badge pada ikon lonceng menunjukkan jumlah notifikasi yang belum dibaca.

## Kategori notifikasi

| Method | Kategori tersimpan | Digunakan untuk | Contoh |
| --- | --- | --- | --- |
| `alert()` | `alert` | Informasi penting atau membutuhkan tindakan | Data belum lengkap, proses gagal, masa berlaku hampir habis |
| `event()` | `event` | Agenda atau kejadian terjadwal | Rapat, tenggat, pemeliharaan sistem |
| `activity()` | `activity` | Riwayat aktivitas bisnis | Data diperbarui, file diunggah, status berubah |
| `log()` | `activity` | Alias dari `activity()` | Dapat digunakan jika istilah `log` lebih sesuai di kode pemanggil |

> `event()` pada service ini adalah kategori tampilan notifikasi. Method tersebut berbeda dengan Laravel Event yang berada di folder `app/Events`.

## Penggunaan dasar

Inject service pada controller, Livewire component, action, listener, job, atau service lain:

```php
use App\Services\NotificationService;

class DataPokokController extends Controller
{
    public function store(NotificationService $notifications)
    {
        // Proses penyimpanan data...

        $notifications->activity(
            auth()->user(),
            'Data berhasil disimpan',
            'Data pokok berhasil diperbarui.',
        );

        return back();
    }
}
```

Service juga dapat diambil langsung dari container Laravel:

```php
$notifications = app(\App\Services\NotificationService::class);
```

## Mengirim alert

Gunakan alert untuk kondisi penting atau sesuatu yang perlu ditindaklanjuti pengguna.

```php
$notifications->alert(
    $user,
    'Data belum lengkap',
    'Silakan lengkapi data pokok sebelum melanjutkan.',
    [
        'severity' => 'warning',
        'url' => '/superadmin/data-pokok',
    ],
);
```

Contoh proses gagal:

```php
$notifications->alert(
    $user,
    'Import data gagal',
    'Beberapa baris tidak dapat diproses. Silakan periksa kembali file Anda.',
    [
        'severity' => 'error',
        'icon' => 'fa-solid fa-file-circle-xmark',
        'url' => '/import/riwayat',
        'meta' => [
            'import_id' => $import->id,
        ],
    ],
);
```

## Mengirim event atau agenda

Gunakan event untuk aktivitas yang memiliki jadwal atau tenggat.

```php
$notifications->event(
    $user,
    'Rapat besok',
    'Rapat koordinasi dimulai pukul 09.00 WIB.',
    [
        'url' => '/agenda/rapat/15',
        'meta' => [
            'event_id' => 15,
            'start_at' => '2026-08-05 09:00:00',
            'end_at' => '2026-08-05 10:30:00',
        ],
    ],
);
```

Notifikasi pengingat terjadwal dapat dipanggil dari Laravel Scheduler atau queued job:

```php
foreach ($agendaBesok as $agenda) {
    $notifications->event(
        $agenda->user,
        'Pengingat agenda',
        "Agenda {$agenda->nama} akan dimulai besok.",
        ['url' => "/agenda/{$agenda->id}"],
    );
}
```

## Mengirim activity atau log

Gunakan activity untuk aktivitas aplikasi yang relevan bagi pengguna. Jangan gunakan method ini untuk exception, stack trace, atau isi mentah `storage/logs/laravel.log`.

```php
$notifications->activity(
    $user,
    'Status pengajuan berubah',
    'Status pengajuan Anda telah disetujui.',
    [
        'severity' => 'success',
        'url' => "/pengajuan/{$pengajuan->id}",
        'actor_id' => auth()->id(),
    ],
);
```

Method `log()` menghasilkan kategori yang sama:

```php
$notifications->log(
    $user,
    'Kategori diperbarui',
    'Nama kategori berhasil diubah.',
    [
        'actor_id' => auth()->id(),
    ],
);
```

## Mengirim kepada banyak pengguna

Parameter pertama dapat berupa satu model `User`, array pengguna, atau collection pengguna.

```php
$users = User::whereIn('id', [1, 2, 3])->get();

$notifications->alert(
    $users,
    'Pemeliharaan sistem',
    'Sistem akan tidak tersedia mulai pukul 22.00 WIB.',
    [
        'severity' => 'warning',
    ],
);
```

Collection kosong dapat dikirim dengan aman dan tidak akan membuat notifikasi apa pun.

## Daftar opsi

Argumen keempat bersifat opsional:

```php
[
    'severity' => 'info',
    'url' => '/halaman-tujuan',
    'icon' => 'fa-solid fa-bell',
    'actor_id' => auth()->id(),
    'meta' => [],
]
```

### `severity`

Menentukan warna ikon notifikasi.

| Nilai | Tampilan umum |
| --- | --- |
| `primary` | Warna utama aplikasi |
| `info` | Biru/informasi |
| `success` | Hijau/berhasil |
| `warning` | Kuning/peringatan |
| `error` atau `danger` | Merah/gagal atau berbahaya |

Nilai default:

- `alert`: `warning`
- `event`: `info`
- `activity` dan `log`: `primary`

### `url`

Alamat internal yang dibuka setelah pengguna mengeklik notifikasi. Klik tersebut sekaligus menandai notifikasi sebagai sudah dibaca.

Gunakan path internal atau URL aplikasi sendiri:

```php
'url' => '/superadmin/data-pokok'
```

atau:

```php
'url' => route('dashboard')
```

URL menuju domain eksternal sengaja tidak diarahkan oleh controller untuk mencegah open redirect.

### `icon`

Class ikon Font Awesome yang digunakan pada dropdown:

```php
'icon' => 'fa-solid fa-triangle-exclamation'
```

Jika tidak diberikan, service menggunakan ikon default sesuai kategori.

### `actor_id`

ID pengguna yang menyebabkan aktivitas. Nilai ini bersifat opsional dan berguna untuk audit atau pengembangan halaman riwayat di kemudian hari.

```php
'actor_id' => auth()->id()
```

### `meta`

Data tambahan berbentuk array. `meta` tidak ditampilkan langsung pada dropdown, tetapi dapat digunakan untuk kebutuhan lanjutan.

```php
'meta' => [
    'pengajuan_id' => $pengajuan->id,
    'status_lama' => 'diproses',
    'status_baru' => 'disetujui',
]
```

Jangan menyimpan password, token, credential, atau data sensitif lain di dalam `meta`.

## Contoh pada Livewire

Service dapat di-resolve di dalam method Livewire:

```php
use App\Services\NotificationService;

public function simpan(): void
{
    // Validasi dan penyimpanan...

    app(NotificationService::class)->activity(
        auth()->user(),
        'Perubahan tersimpan',
        'Data berhasil diperbarui.',
        ['url' => request()->path()],
    );
}
```

Jika menggunakan `request()->path()`, tambahkan garis miring agar menjadi path internal:

```php
'url' => '/'.ltrim(request()->path(), '/')
```

## Perilaku UI

- Dropdown mengambil maksimal 15 notifikasi terbaru milik pengguna login.
- Tab **Semua** menampilkan seluruh kategori.
- Tab **Peringatan** menampilkan `alert`.
- Tab **Agenda** menampilkan `event`.
- Tab **Aktivitas** menampilkan `activity` dan pemanggilan melalui `log()`.
- Badge menunjukkan jumlah seluruh notifikasi yang belum dibaca.
- Mengeklik item menandainya sebagai sudah dibaca dan membuka `url` jika tersedia.
- Tombol **Baca semua** menandai seluruh notifikasi pengguna sebagai sudah dibaca.

## Validasi dan catatan

- Judul dan pesan wajib diisi. String kosong akan menghasilkan `InvalidArgumentException`.
- Penerima harus berupa model yang menggunakan trait Laravel `Notifiable`, array, atau collection model tersebut.
- Notifikasi hanya tersimpan di database. Service belum mengirim email, WhatsApp, Slack, atau push notification.
- Pemanggilan service saat ini bersifat sinkron. Jika volume penerima sangat besar, panggil service melalui queued job.
- Setiap pengguna hanya dapat menandai notifikasi miliknya sendiri sebagai sudah dibaca.

## Referensi implementasi

- Service: `app/Services/NotificationService.php`
- Notification class: `app/Notifications/ApplicationNotification.php`
- Kategori: `app/Enums/NotificationCategory.php`
- Controller aksi baca: `app/Http/Controllers/NotificationController.php`
- UI dropdown: `resources/views/components/notifications/dropdown.blade.php`
- Migration: `database/migrations/2026_08_04_000000_create_notifications_table.php`
- Test: `tests/Feature/NotificationTest.php`
