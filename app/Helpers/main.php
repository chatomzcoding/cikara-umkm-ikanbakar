<?php

use Illuminate\Support\Str;

if (! function_exists('preview_text')) {
    function preview_text(string $html, int $limit = 100): string
    {
        return Str::limit(strip_tags($html), $limit);
    }
}
if (! function_exists('rupiah')) {
    function rupiah($angka)
    {
        $hasil_rupiah = null;
        if (! is_null($angka) and ! empty($angka)) {
            $hasil_rupiah = number_format($angka, 2, ',', '.');
            $hasil_rupiah = str_replace(',00', '', $hasil_rupiah);
        }

        return $hasil_rupiah;
    }
}
if (! function_exists('norupiah')) {
    function norupiah($angka, $custom = null)
    {
        if ($angka == '0') {
            return $angka;
        } else {
            $hasil_rupiah = $custom;
            if (! is_null($angka) and ! empty($angka)) {
                $hasil_rupiah = number_format($angka, 2, ',', '.');
                $hasil_rupiah = str_replace(',00', '', $hasil_rupiah);
            }
            if ($hasil_rupiah == '' || $hasil_rupiah == null) {
                return 0;
            } else {
                return $hasil_rupiah;
            }
        }
    }
}

if (! function_exists('terbilang')) {
    function terbilang($bilangan)
    {
        $kata = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];
        $tingkat = ['', 'ribu', 'juta', 'milyar', 'triliun'];

        $bilangan = ltrim($bilangan, '0');
        if (strlen($bilangan) > 15) {
            return 'Bilangan Diluar Batas';
        }

        // Pecah jadi grup 3 digit dari belakang
        $bilangan = str_pad($bilangan, ceil(strlen($bilangan) / 3) * 3, '0', STR_PAD_LEFT);
        $kelompok = str_split($bilangan, 3);
        $kalimat = '';

        foreach ($kelompok as $i => $group) {
            $ratus = (int) $group[0];
            $puluh = (int) $group[1];
            $satu = (int) $group[2];
            $idx = count($kelompok) - $i - 1;

            $sub = '';
            // Ratusan
            if ($ratus > 0) {
                $sub .= $ratus == 1 ? 'seratus ' : $kata[$ratus].' ratus ';
            }
            // Puluhan & Belasan
            if ($puluh > 0) {
                if ($puluh == 1) {
                    if ($satu == 0) {
                        $sub .= 'sepuluh ';
                    } elseif ($satu == 1) {
                        $sub .= 'sebelas ';
                    } else {
                        $sub .= $kata[$satu].' belas ';
                    }
                } else {
                    $sub .= $kata[$puluh].' puluh ';
                }
            }
            // Satuan
            if ($puluh != 1 && $satu > 0) {
                $sub .= $kata[$satu].' ';
            }
            // Tambah tingkat jika group tidak nol semua
            if (trim($sub) !== '') {
                $sub .= $tingkat[$idx].' ';
            }
            $kalimat .= $sub;
        }
        // Ubah "satu ribu" jadi "seribu"
        $kalimat = preg_replace('/\bsatu ribu\b/', 'seribu', trim($kalimat));

        return trim($kalimat);
    }
}

// nama negara
if (! function_exists('default_nilai')) {
    function default_nilai($nilai)
    {
        if (! is_null($nilai)) {
            $nilai = str_replace('Rp. ', '', $nilai);
            $nilai = str_replace('.', '', $nilai);
        }

        return $nilai;
    }
}

// # mengambil tanggal hari ini
if (! function_exists('tgl_sekarang')) {
    function tgl_sekarang()
    {
        return date('Y-m-d');
    }
}

// # ambil tanggal hari ini
if (! function_exists('ambil_tgl')) {
    function ambil_tgl()
    {
        return date('d');
    }
}

// # ambil bulan hari ini
if (! function_exists('ambil_bulan')) {
    function ambil_bulan()
    {
        return date('m');
    }
}

// # ambil tahun hari ini
if (! function_exists('ambil_tahun')) {
    function ambil_tahun()
    {
        return date('Y');
    }
}

// # mengambil Waktu hari ini
if (! function_exists('jam_sekarang')) {
    function jam_sekarang()
    {
        return date('H:i:s');
    }
}

// date indo
if (! function_exists('bulan_indo')) {
    function bulan_indo($m = null)
    {
        if (is_null($m)) {
            $m = ambil_bulan();
        } else {
            if (strlen($m) == 1) {
                $m = '0'.$m;
            }
        }

        $bulan = ['01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'];

        return $bulan[$m];
    }
}

if (! function_exists('bulan_indoSingkat')) {
    function bulan_indoSingkat($m = null)
    {
        if (is_null($m)) {
            $m = ambil_bulan();
        } else {
            if (strlen($m) == 1) {
                $m = '0'.$m;
            }
        }

        $bulan = ['01' => 'J',
            '02' => 'F',
            '03' => 'M',
            '04' => 'A',
            '05' => 'M',
            '06' => 'J',
            '07' => 'J',
            '08' => 'A',
            '09' => 'S',
            '10' => 'O',
            '11' => 'N',
            '12' => 'D'];

        return $bulan[$m];
    }
}

// change date to indonesia date
if (! function_exists('date_indo')) {
    function date_indo($date = null, $info = null)
    {
        if (! is_null($date) and $date != '0000-00-00') {
            $tgl = substr($date, 8, 2);
            $bulan = substr($date, 5, 2);
            $tahun = substr($date, 0, 4);

            return $tgl.' '.bulan_indo($bulan).' '.$tahun;
        } else {
            $notif = 'Date Not Found';
            if (! is_null($info)) {
                $notif = $info;
            }

            return $notif;
        }
    }
}
// date indo
if (! function_exists('daftar_hari')) {
    function daftar_hari($data)
    {
        $hari = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        return $hari[$data];
    }
}
if (! function_exists('daftar_bulan')) {
    function daftar_bulan()
    {
        $bulan = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];

        return $bulan;
    }
}

// get nama hari
if (! function_exists('hari_indo')) {
    function hari_indo($tanggal)
    {
        $namahari = date('l', strtotime($tanggal));

        return daftar_hari($namahari);
    }
}

// cek waktu
if (! function_exists('kingdom_rentang_waktu')) {
    function kingdom_rentang_waktu($tgl_akhir)
    {
        $tgl2 = new Datetime($tgl_akhir);
        $now = new Datetime;
        $rentang = $tgl2->diff($now);

        return $rentang->d;
    }
}

if (! function_exists('viewSelect2array')) {
    function viewSelect2array($data, $field)
    {
        $result = $data->map(function ($item) use ($field) {
            return [
                'id' => $item->id,
                'text' => $item->$field,
            ];
        });

        return $result;
    }
}

if (! function_exists('session_default')) {
    function session_default($key, $default)
    {
        if (! session()->has($key)) {
            $value = is_callable($default) ? $default() : $default;
            session([$key => $value]);
        }

        return session($key);
    }
}

// END TEMA

if (! function_exists('main_link')) {
    function main_link(): array
    {
        $app = env('APP_ENV', 'production');
        if ($app == 'local') {
            $base_url = env('APP_URL', 'https://linqo-app.test');
            $linkgo = $base_url.'/public/go';
        } else {
            $base_url = 'https://linqo.id';
            $linkgo = 'https://go.linqo.id';
        }

        return [
            'go' => $linkgo,
            'base_url' => $base_url,
        ];
    }
}

if (! function_exists('getlink')) {
    function getlink($s): string
    {
        $app = env('APP_ENV', 'production');
        $result = ($app == 'local') ? 'public/'.$s : '';

        return $result;
    }
}
