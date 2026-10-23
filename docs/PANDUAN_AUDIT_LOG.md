# Panduan Audit Log

Audit log menyimpan bukti aktivitas penting sistem secara read-only. Halaman tersedia melalui menu **Master → Audit Log** untuk pengguna dengan permission `audit-logs.view`.

## Aktivitas yang dicatat

- create, update, delete, soft delete, restore, dan force delete model yang memakai trait `Auditable`;
- login berhasil, login gagal, dan logout;
- perubahan role pengguna dan permission role;
- export CSV, Excel, dan PDF;
- setiap item bulk action dengan `batch_uuid` yang sama.

Model inti User, Role, Permission, Data Pokok, Kategori, Subkategori, dan model hasil generator sudah menggunakan audit. Pada model manual, aktifkan dengan:

```php
use App\Models\Concerns\Auditable;

class Product extends Model
{
    use Auditable;
}
```

Command `make:crud` menanyakan **Aktifkan audit log untuk CRUD ini?**. Mode non-interaktif menggunakan `--audit` atau `--no-audit`.

## Pencatatan manual

Aktivitas bisnis di luar event model dapat dicatat melalui:

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

## Keamanan

- Tidak tersedia fitur edit atau delete dari halaman audit.
- Snapshot nama dan email pelaku tetap disimpan walaupun user dihapus.
- Password, token, secret, recovery code, dan data 2FA selalu menjadi `[REDACTED]`.
- Tabel tidak memakai foreign key cascade ke user atau objek asal.
- Akses tampilan dan export dipisahkan melalui `audit-logs.view` dan `audit-logs.export`.

Field sensitif tambahan dapat didaftarkan pada `config/audit.php`. Audit dapat dimatikan sementara melalui `AUDIT_LOG_ENABLED=false`, tetapi tidak disarankan pada production.

## Operasional

Audit log dapat tumbuh besar. Tentukan retensi sesuai kebijakan organisasi, backup sebelum purge, dan batasi akses database langsung. Penghapusan retensi sebaiknya dilakukan melalui prosedur administratif terpisah agar tidak tersedia dari UI aplikasi.
