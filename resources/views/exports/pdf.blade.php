{{--
    View default cetak PDF tabel CRUD.

    Variabel yang tersedia:
    - $title, $subtitle, $meta (array label => nilai)
    - $headings (array judul kolom), $rows (array baris berisi string)
    - $records (koleksi Eloquent asli, untuk view kustom)
    - $identity (kop surat), $printedAt, $orientation

    Untuk layout khusus, salin file ini lalu tunjuk lewat crudPdfView() di halaman index.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 100px 28px 55px 28px; }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #1e293b;
        }

        header { position: fixed; top: -80px; left: 0; right: 0; height: 72px; }
        footer { position: fixed; bottom: -40px; left: 0; right: 0; height: 30px; }

        .kop { width: 100%; border-bottom: 1.5px solid #334155; padding-bottom: 6px; }
        .kop td { vertical-align: middle; }
        .kop-logo { width: 58px; }
        .kop-logo img { width: 52px; height: auto; }
        .kop-nama { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; }
        .kop-detail { font-size: 8.5px; color: #475569; margin-top: 2px; }

        .judul { text-align: center; margin-bottom: 10px; }
        .judul h1 { font-size: 12.5px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .judul p { font-size: 9px; color: #64748b; margin: 3px 0 0; }

        .meta { width: 100%; font-size: 8.5px; color: #475569; margin-bottom: 8px; }
        .meta td { padding: 1px 0; }
        .meta .meta-label { width: 110px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th,
        table.data td { border: 0.5px solid #94a3b8; padding: 4px 6px; }
        table.data th {
            background-color: #e2e8f0;
            font-size: 9px;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
        }
        table.data td { vertical-align: top; }
        table.data tr:nth-child(even) td { background-color: #f8fafc; }
        .col-no { width: 28px; text-align: center; }
        .kosong { text-align: center; font-style: italic; color: #64748b; padding: 14px; }

        .footer-bar { width: 100%; border-top: 0.5px solid #cbd5e1; padding-top: 4px; font-size: 8px; color: #64748b; }
        .footer-bar td { vertical-align: top; }
        .halaman { text-align: right; }
        .halaman:after { content: "Halaman " counter(page) " dari " counter(pages); }
    </style>
</head>
<body>
    <header>
        <table class="kop">
            <tr>
                @if (! empty($identity['logo']))
                    <td class="kop-logo"><img src="{{ $identity['logo'] }}" alt=""></td>
                @endif
                <td>
                    <div class="kop-nama">{{ $identity['nama'] }}</div>
                    <div class="kop-detail">
                        {{ $identity['alamat'] }}
                        @if ($identity['no_telp']) &middot; Telp. {{ $identity['no_telp'] }} @endif
                        @if ($identity['email']) &middot; {{ $identity['email'] }} @endif
                        @if ($identity['url_website']) &middot; {{ $identity['url_website'] }} @endif
                    </div>
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table class="footer-bar">
            <tr>
                <td>Dicetak {{ $printedAt->translatedFormat('d F Y H:i') }} &middot; {{ $identity['nama'] }}</td>
                <td class="halaman"></td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="judul">
            <h1>{{ $title }}</h1>
            @if ($subtitle)
                <p>{{ $subtitle }}</p>
            @endif
        </div>

        @if (! empty($meta))
            <table class="meta">
                @foreach ($meta as $label => $value)
                    <tr>
                        <td class="meta-label">{{ $label }}</td>
                        <td>: {{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <table class="data">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    @foreach ($headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                    <tr>
                        <td class="col-no">{{ $index + 1 }}</td>
                        @foreach ($row as $value)
                            <td>{{ $value }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td class="kosong" colspan="{{ count($headings) + 1 }}">Tidak ada data untuk dicetak.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
