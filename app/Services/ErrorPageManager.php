<?php

namespace App\Services;

use App\Models\ErrorPageSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ErrorPageManager
{
    public const SUPPORTED_CODES = [403, 404, 419, 429, 500, 503];

    public const ACCENTS = [
        'indigo' => '#4f46e5',
        'rose' => '#e11d48',
        'amber' => '#d97706',
        'sky' => '#0284c7',
        'slate' => '#475569',
    ];

    public static function defaults(): array
    {
        return [
            403 => ['title' => 'Akses ditolak', 'message' => 'Anda tidak memiliki izin untuk membuka halaman ini.', 'accent' => 'rose', 'button_label' => 'Ke dashboard', 'button_url' => '/dashboard', 'show_back_button' => true],
            404 => ['title' => 'Halaman tidak ditemukan', 'message' => 'Halaman yang Anda cari mungkin telah dipindahkan atau tidak tersedia.', 'accent' => 'indigo', 'button_label' => 'Ke dashboard', 'button_url' => '/dashboard', 'show_back_button' => true],
            419 => ['title' => 'Sesi telah berakhir', 'message' => 'Muat ulang halaman atau login kembali untuk melanjutkan.', 'accent' => 'amber', 'button_label' => 'Login kembali', 'button_url' => '/login', 'show_back_button' => false],
            429 => ['title' => 'Terlalu banyak permintaan', 'message' => 'Tunggu beberapa saat sebelum mencoba kembali.', 'accent' => 'amber', 'button_label' => 'Kembali ke beranda', 'button_url' => '/', 'show_back_button' => true],
            500 => ['title' => 'Terjadi kesalahan sistem', 'message' => 'Permintaan belum dapat diproses. Tim kami dapat menelusurinya melalui kode referensi di bawah.', 'accent' => 'rose', 'button_label' => 'Kembali ke beranda', 'button_url' => '/', 'show_back_button' => true],
            503 => ['title' => 'Layanan sedang tidak tersedia', 'message' => 'Sistem sedang dalam pemeliharaan. Silakan coba kembali beberapa saat lagi.', 'accent' => 'sky', 'button_label' => 'Coba lagi', 'button_url' => '/', 'show_back_button' => false],
        ];
    }

    public function get(int $statusCode): array
    {
        $fallback = self::defaults()[$statusCode] ?? self::defaults()[500];

        try {
            if (! Schema::hasTable('error_page_settings')) {
                return $this->decorate($statusCode, $fallback);
            }

            $setting = Cache::rememberForever("error-page.{$statusCode}", fn () => ErrorPageSetting::query()
                ->where('status_code', $statusCode)
                ->first());

            if (! $setting?->is_active) {
                return $this->decorate($statusCode, $fallback);
            }

            return $this->decorate($statusCode, [
                'title' => $setting->title,
                'message' => $setting->message,
                'accent' => $setting->accent,
                'button_label' => $setting->button_label,
                'button_url' => $setting->button_url,
                'show_back_button' => $setting->show_back_button,
            ]);
        } catch (Throwable) {
            return $this->decorate($statusCode, $fallback);
        }
    }

    public function forget(int $statusCode): void
    {
        Cache::forget("error-page.{$statusCode}");
    }

    private function decorate(int $statusCode, array $settings): array
    {
        $accent = array_key_exists($settings['accent'], self::ACCENTS) ? $settings['accent'] : 'indigo';

        return [
            'status_code' => $statusCode,
            ...$settings,
            'accent' => $accent,
            'accent_color' => self::ACCENTS[$accent],
        ];
    }
}
