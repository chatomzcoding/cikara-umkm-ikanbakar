<?php

namespace App\Support\Crud;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Layanan cetak PDF untuk tabel CRUD.
 *
 * Halaman index cukup menentukan data (kolom) dan view-nya; format kertas,
 * kop surat, dan penomoran halaman ditangani di sini.
 */
class PdfExporter
{
    public const DEFAULT_VIEW = 'exports.pdf';

    /** Identitas aplikasi di-cache agar tidak query berulang dalam satu request. */
    private ?array $identity = null;

    /**
     * @param  array<string, string|Closure(Model): mixed>  $columns
     * @param  array<string, mixed>  $options
     */
    public function download(Builder|EloquentCollection $source, array $columns, string $filename, array $options = []): StreamedResponse
    {
        // streamDownload dipakai agar respons kompatibel dengan file download Livewire,
        // sama seperti CsvExporter.
        $output = $this->make($source, $columns, $options)->output();

        return response()->streamDownload(function () use ($output): void {
            echo $output;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Tampilkan PDF di browser (inline), untuk dipakai dari route/controller biasa.
     *
     * @param  array<string, string|Closure(Model): mixed>  $columns
     * @param  array<string, mixed>  $options
     */
    public function stream(Builder|EloquentCollection $source, array $columns, string $filename, array $options = []): Response
    {
        return $this->make($source, $columns, $options)->stream($filename);
    }

    /**
     * @param  array<string, string|Closure(Model): mixed>  $columns
     * @param  array<string, mixed>  $options
     */
    public function make(Builder|EloquentCollection $source, array $columns, array $options = []): DomPdf
    {
        $records = $source instanceof Builder ? $source->get() : $source;

        $data = [
            'title' => $options['title'] ?? 'Laporan Data',
            'subtitle' => $options['subtitle'] ?? null,
            'meta' => $options['meta'] ?? [],
            'headings' => array_keys($columns),
            'rows' => $this->rows($records, $columns),
            'records' => $records,
            'columns' => $columns,
            'identity' => $this->identity(),
            'printedAt' => now(),
            'orientation' => $options['orientation'] ?? 'portrait',
        ];

        return Pdf::loadView($options['view'] ?? self::DEFAULT_VIEW, array_merge($data, $options['with'] ?? []))
            ->setPaper($options['paper'] ?? 'a4', $data['orientation']);
    }

    /**
     * @param  array<string, string|Closure(Model): mixed>  $columns
     * @return array<int, array<int, string>>
     */
    private function rows(EloquentCollection $records, array $columns): array
    {
        return $records
            ->map(fn (Model $record) => array_map(
                fn (string|Closure $resolver) => ExportValue::format($record, $resolver, 'd/m/Y H:i'),
                array_values($columns),
            ))
            ->all();
    }

    /**
     * Identitas aplikasi untuk kop surat. Diambil dari Data Pokok bila modulnya tersedia.
     *
     * @return array<string, mixed>
     */
    private function identity(): array
    {
        if ($this->identity !== null) {
            return $this->identity;
        }

        $fallback = [
            'nama' => config('app.name'),
            'alamat' => null,
            'no_telp' => null,
            'email' => null,
            'url_website' => null,
            'logo' => null,
        ];

        $modelClass = '\Modules\Superadmin\Models\DataPokok';

        if (! class_exists($modelClass)) {
            return $this->identity = $fallback;
        }

        try {
            $pokok = $modelClass::query()->first();
        } catch (Throwable) {
            // Tabel data_pokok belum dimigrasi — cetak tetap jalan tanpa kop.
            return $this->identity = $fallback;
        }

        if ($pokok === null) {
            return $this->identity = $fallback;
        }

        return $this->identity = [
            'nama' => $pokok->nama_aplikasi ?: config('app.name'),
            'alamat' => $pokok->alamat,
            'no_telp' => $pokok->no_telp,
            'email' => $pokok->email,
            'url_website' => $pokok->url_website,
            'logo' => $this->embeddedLogo($pokok->logo),
        ];
    }

    /**
     * Dompdf tidak bisa mengambil aset lewat URL, jadi logo disisipkan sebagai data URI.
     */
    private function embeddedLogo(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            $disk = Storage::disk('public');

            if (! $disk->exists($path)) {
                return null;
            }

            $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                default => 'image/png',
            };

            return 'data:'.$mime.';base64,'.base64_encode((string) $disk->get($path));
        } catch (Throwable) {
            return null;
        }
    }
}
