# Panduan Halaman Error Dinamis

Fitur ini mengatur halaman error `403`, `404`, `419`, `429`, `500`, dan `503` melalui menu **Master → Halaman Error**.

## Permission

- `error-pages.view`: membuka daftar pengaturan dan preview.
- `error-pages.update`: menyimpan perubahan dan mengembalikan pengaturan awal.

Super Admin memperoleh kedua permission secara otomatis dari `PermissionSeeder`.

## Pengaturan yang tersedia

Setiap kode error memiliki pengaturan aktif/nonaktif, judul, pesan, warna aksen, label dan URL tombol utama, serta tombol kembali. URL tombol utama hanya menerima URL internal yang diawali `/`. Konten disimpan sebagai teks biasa dan tidak menjalankan HTML dari input admin.

Tombol **Preview** membuka hasil tampilan pada tab baru dengan status HTTP 200, sehingga aman digunakan saat melakukan penyuntingan. Tombol **Reset** mengembalikan satu kode error ke konten awal bawaan sistem.

Jika opsi **Gunakan pengaturan custom** dimatikan, halaman menggunakan konten fallback bawaan. Fallback juga otomatis digunakan saat tabel pengaturan atau database tidak dapat dibaca, termasuk ketika error berasal dari gangguan database.

## Request ID

Setiap request memperoleh kode UUID yang:

- Ditampilkan sebagai kode referensi pada halaman error.
- Ditambahkan ke konteks log aplikasi.
- Dikirim melalui header respons `X-Request-ID`.

Kode tersebut dapat digunakan administrator untuk mencocokkan laporan pengguna dengan log server tanpa menampilkan stack trace atau informasi teknis sensitif.
