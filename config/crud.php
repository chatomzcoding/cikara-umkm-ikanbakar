<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cetak PDF
    |--------------------------------------------------------------------------
    |
    | Pengaturan default untuk tombol "Cetak PDF" pada tabel CRUD. Setiap
    | halaman index masih bisa menimpanya lewat method crudPdf*() pada
    | komponennya masing-masing.
    |
    */

    'pdf' => [

        // View default yang dipakai untuk merender tabel.
        'view' => 'exports.pdf',

        // Ukuran kertas dompdf: a4, letter, legal, f4, dst.
        'paper' => 'a4',

        // portrait atau landscape.
        'orientation' => 'portrait',

        // Batas aman jumlah baris. Di atas ini cetak ditolak agar tidak
        // menghabiskan memori — arahkan pengguna memfilter data atau ekspor CSV.
        'max_rows' => (int) env('CRUD_PDF_MAX_ROWS', 2000),

    ],

];
