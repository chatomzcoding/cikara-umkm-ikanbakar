<?php

use App\Models\User;
use Modules\Superadmin\Models\DataPokok;
use Modules\Superadmin\Models\Sistemkategori;
use Modules\Superadmin\Models\Sistemkategorisub;

return [
    'minimum_characters' => 2,
    'dropdown_limit' => 8,
    'page_limit' => 50,
    'recent_limit' => 5,

    /*
    |--------------------------------------------------------------------------
    | Sumber pencarian
    |--------------------------------------------------------------------------
    |
    | Tambahkan sumber baru di sini. Placeholder seperti {name} atau
    | {sistemkategori.uuid} diambil dari atribut/relasi model.
    |
    */
    'sources' => [
        'users' => [
            'model' => User::class,
            'label' => 'Pengguna',
            'icon' => 'P',
            'icon_class' => 'bg-info/10 text-info dark:bg-info/15',
            'searchable' => ['name', 'email'],
            'title' => '{name}',
            'subtitle' => '{email}',
            'route' => 'superadmin.sistem.user.index',
            'query' => ['search' => '{name}'],
        ],
        'data_pokok' => [
            'model' => DataPokok::class,
            'label' => 'Data Pokok',
            'icon' => 'D',
            'icon_class' => 'bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light',
            'searchable' => ['nama_aplikasi', 'nama_owner', 'email', 'no_telp', 'url_website'],
            'title' => '{nama_aplikasi}',
            'subtitle' => 'Pemilik: {nama_owner}',
            'route' => 'superadmin.sistem.data-pokok.index',
            'query' => ['search' => '{nama_aplikasi}'],
        ],
        'sistem_kategori' => [
            'model' => Sistemkategori::class,
            'label' => 'Kategori Sistem',
            'icon' => 'K',
            'icon_class' => 'bg-success/10 text-success dark:bg-success/15',
            'searchable' => ['nama', 'keterangan'],
            'title' => '{nama}',
            'subtitle' => '{keterangan}',
            'route' => 'superadmin.sistem.kategori.show',
            'route_parameters' => ['kategori' => '{uuid}'],
        ],
        'sistem_kategori_sub' => [
            'model' => Sistemkategorisub::class,
            'label' => 'Subkategori Sistem',
            'icon' => 'S',
            'icon_class' => 'bg-warning/10 text-warning dark:bg-warning/15',
            'with' => ['sistemkategori'],
            'searchable' => ['nama', 'keterangan'],
            'title' => '{nama}',
            'subtitle' => 'Kategori: {sistemkategori.nama}',
            'route' => 'superadmin.sistem.kategori.show',
            'route_parameters' => ['kategori' => '{sistemkategori.uuid}'],
            'query' => ['search' => '{nama}'],
        ],
    ],
];
