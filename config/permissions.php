<?php

return [
    'groups' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'permissions' => [
                'dashboard.view' => 'Melihat dashboard sistem',
            ],
        ],
        'data-pokok' => [
            'label' => 'Data Pokok',
            'permissions' => [
                'data-pokok.view' => 'Melihat data pokok',
                'data-pokok.update' => 'Mengubah data pokok',
            ],
        ],
        'users' => [
            'label' => 'Pengguna',
            'permissions' => [
                'users.view' => 'Melihat pengguna',
                'users.create' => 'Menambah pengguna',
                'users.update' => 'Mengubah pengguna',
                'users.delete' => 'Menghapus pengguna',
                'users.export' => 'Mengekspor data pengguna',
                'users.restore' => 'Memulihkan pengguna dari sampah',
                'users.force-delete' => 'Menghapus pengguna secara permanen',
                'users.assign-roles' => 'Menetapkan role pengguna',
                'users.suspend' => 'Menangguhkan pengguna',
                'users.activate' => 'Mengaktifkan kembali pengguna',
                'users.deactivate' => 'Menonaktifkan pengguna',
                'users.impersonate' => 'Masuk sebagai pengguna lain',
            ],
        ],
        'roles' => [
            'label' => 'Role dan Permission',
            'permissions' => [
                'roles.view' => 'Melihat role',
                'roles.create' => 'Menambah role',
                'roles.update' => 'Mengubah role',
                'roles.delete' => 'Menghapus role',
                'roles.assign-permissions' => 'Menetapkan permission ke role',
            ],
        ],
        'audit-logs' => [
            'label' => 'Audit Log',
            'permissions' => [
                'audit-logs.view' => 'Melihat bukti aktivitas sistem',
                'audit-logs.export' => 'Mengekspor audit log',
            ],
        ],
        'error-pages' => [
            'label' => 'Halaman Error',
            'permissions' => [
                'error-pages.view' => 'Melihat pengaturan halaman error',
                'error-pages.update' => 'Mengubah pengaturan halaman error',
            ],
        ],
        'categories' => [
            'label' => 'Kategori',
            'permissions' => [
                'categories.view' => 'Melihat kategori',
                'categories.create' => 'Menambah kategori',
                'categories.update' => 'Mengubah kategori',
                'categories.delete' => 'Menghapus kategori',
            ],
        ],
        'subcategories' => [
            'label' => 'Subkategori',
            'permissions' => [
                'subcategories.view' => 'Melihat subkategori',
                'subcategories.create' => 'Menambah subkategori',
                'subcategories.update' => 'Mengubah subkategori',
                'subcategories.delete' => 'Menghapus subkategori',
            ],
        ],
    ],

    'role_defaults' => [
        'admin' => [
            'display_name' => 'Admin',
            'description' => 'Mengakses menu Master: data pokok, audit log, dan pengguna.',
            'permissions' => [
                'data-pokok.view',
                'audit-logs.view',
                'users.view',
            ],
        ],
    ],
];
