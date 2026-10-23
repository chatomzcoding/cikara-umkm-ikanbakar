<?php

if (! function_exists('data_golongandarah')) {
    function data_golongandarah()
    {
        $data = ['Tidak Tahu', 'A', 'B', 'AB', 'AB'];

        return $data;
    }
}

if (! function_exists('data_jeniskelamin')) {
    function data_jeniskelamin()
    {
        $data = [
            '1' => 'Laki-laki',
            '2' => 'Perempuan',
        ];

        return $data;
    }
}

if (! function_exists('data_pernikahan')) {
    function data_pernikahan()
    {
        $data = ['belum nikah', 'sudah nikah', 'pernah nikah'];

        return $data;
    }
}

if (! function_exists('data_agama')) {
    function data_agama()
    {
        $data = [
            '1' => 'islam',
            '2' => 'katolik',
            '3' => 'protestan',
            '4' => 'hindu',
            '5' => 'budha',
            '6' => 'konghucu',
        ];

        return $data;
    }
}

if (! function_exists('data_jenis_rekening')) {
    function data_jenis_rekening()
    {
        $jenis = [
            '1' => 'cash',
            '2' => 'bank',
            '3' => 'e-money',
        ];

        return $jenis;
    }
}
if (! function_exists('data_anggaran_jenis')) {
    function data_anggaran_jenis()
    {
        $result = [
            '1' => 'sekali',
            '2' => 'bulanan',
        ];

        return $result;
    }
}
if (! function_exists('data_anggaran_prioritas')) {
    function data_anggaran_prioritas()
    {
        $result = [
            '1' => 'primer',
            '2' => 'sekunder',
        ];

        return $result;
    }
}
if (! function_exists('data_anggaran_status')) {
    function data_anggaran_status()
    {
        $result = [
            '1' => 'proses',
            '2' => 'selesai',
            '3' => 'batal',
        ];

        return $result;
    }
}

if (! function_exists('data_keuangan_arus')) {
    function data_keuangan_arus()
    {
        $result = [
            2 => 'PENGELUARAN',
            21 => 'TRANSFER PENGELUARAN',
            1 => 'PEMASUKAN',
            11 => 'TRANSFER PEMASUKAN',
            3 => 'HUTANG',
            4 => 'PIUTANG',
        ];

        return $result;
    }
}

if (! function_exists('data_aplikasi')) {
    function data_aplikasi()
    {
        $result = [
            'chatomz',
            'digilaku',
            'undangku',
        ];

        return $result;
    }
}

if (! function_exists('data_status_keluarga_list')) {
    function data_status_keluarga_list()
    {
        $result = [
            'istri',
            'anak',
            'anak angkat',
            'suami',
        ];

        return $result;
    }
}
