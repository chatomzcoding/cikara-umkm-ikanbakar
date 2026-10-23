# Panduan Siklus Hidup User — Tahap 1

Tahap pertama menyediakan tiga status akun:

| Status | Akses login | Kegunaan |
| --- | --- | --- |
| Aktif | Diizinkan | Pengguna bekerja seperti biasa. |
| Ditangguhkan | Ditolak | Menghentikan akses sementara, misalnya saat pemeriksaan. |
| Nonaktif | Ditolak | Menghentikan akses sampai admin mengaktifkan kembali akun. |

Soft delete tetap terpisah dari status. User yang dipindahkan ke sampah dapat dipulihkan melalui fitur restore, sedangkan perubahan status tidak menghapus data user.

## Mengatur permission

Berikan permission berikut melalui menu **Role & Permission**:

- `users.suspend`: menangguhkan akun aktif.
- `users.activate`: mengaktifkan kembali akun yang ditangguhkan atau nonaktif.
- `users.deactivate`: menonaktifkan akun aktif.

Super Admin otomatis memperoleh ketiganya dari `PermissionSeeder`.

## Cara menggunakan

1. Buka **Master → User**.
2. Gunakan filter status bila diperlukan.
3. Pada user aktif, pilih **Suspend** atau **Nonaktifkan**.
4. Isi alasan pada dialog konfirmasi.
5. Untuk memulihkan akses, pilih **Aktifkan**.

Perubahan berlaku langsung. User yang masih memiliki sesi akan dikeluarkan pada request berikutnya, sedangkan login baru akan ditolak.

## Data dan audit

Sistem menyimpan waktu perubahan, admin pelaksana, dan alasan. Audit Log mencatat event:

- `user_suspended`
- `user_activated`
- `user_deactivated`

Selain event tersebut, perubahan atribut model juga tetap tercatat oleh audit model.

## Aturan pengaman

- Admin tidak dapat suspend atau menonaktifkan akun yang sedang dipakainya.
- Super Admin aktif terakhir tidak dapat suspend atau dinonaktifkan.
- Alasan wajib diisi untuk suspend dan nonaktifkan.
- User dengan status ditangguhkan atau nonaktif tidak dapat login.

Tahap undangan user dan penguncian otomatis karena gagal login belum diterapkan sesuai batas tahap pertama.

## Impersonasi user

Permission `users.impersonate` menampilkan tombol **Impersonate** pada akun aktif. Fitur ini membantu admin memeriksa tampilan dan akses yang benar-benar dialami user tanpa mengetahui password user.

Selama impersonasi, banner peringatan ditampilkan di bagian atas halaman. Gunakan tombol **Kembali ke akun admin** pada banner untuk mengakhiri sesi impersonasi.

Aturan pengamannya:

- Akun sendiri dan akun nonaktif tidak dapat diimpersonasi.
- Impersonasi tidak dapat ditumpuk atau dijalankan secara bertingkat.
- Super Admin hanya dapat diimpersonasi oleh Super Admin lain.
- Mulai dan selesai impersonasi dicatat sebagai `impersonation_started` dan `impersonation_ended` di Audit Log.
