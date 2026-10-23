@props([
    'title',
    'description' => null,
    'pageTitle' => null,
])
@php
    $appData = \Illuminate\Support\Facades\Schema::hasTable('data_pokok')
        ? \Modules\Superadmin\Models\DataPokok::query()->first()
        : null;
    $appName = $appData?->nama_aplikasi ?? config('app.name', 'PANEL');
    $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');
    $appLogo = $appData?->logo && $publicDisk->exists($appData->logo)
        ? $publicDisk->url($appData->logo)
        : asset('img/app-logo.svg');
    $description ??= $appData?->keterangan ?: 'Silahkan login disini!';
    $status = match (session('status')) {
        'verification-link-sent' => 'Tautan verifikasi baru sudah dikirim ke email Anda.',
        default => session('status'),
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />

    <title>{{ $appName }} - {{ $pageTitle ?? $title }}</title>
    <x-favicon />

    <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('js/pages/pages-sign-in-2.js') }}" defer></script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />
    <script>
      // Mencegah kedip saat mode gelap aktif.
      localStorage.getItem("dark-mode") === "dark" && document.documentElement.classList.add("dark");
    </script>
  </head>

  <body class="is-header-blur is-sidebar-open">
    <div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900">
      <div class="app-preloader-inner relative inline-block size-48"></div>
    </div>

    <div id="root" class="min-h-100vh cloak flex grow bg-slate-50 dark:bg-navy-900">
      <div class="fixed top-0 hidden p-6 lg:block lg:px-12">
        <a href="{{ route('login') }}" class="flex items-center space-x-2">
          <img class="size-12" src="{{ $appLogo }}" alt="{{ $appName }}" />
          <p class="text-xl font-semibold uppercase text-slate-700 dark:text-navy-100">{{ $appName }}</p>
        </a>
      </div>
      <div class="hidden w-full place-items-center lg:grid">
        <div class="w-full max-w-lg p-6">
          <img class="w-full" id="hero-image-light" src="{{ asset('img/illustrations/dashboard-check.svg') }}" alt="" />
          <img class="w-full" id="hero-image-dark" src="{{ asset('img/illustrations/dashboard-check-dark.svg') }}" alt="" />
        </div>
      </div>
      <main class="flex w-full flex-col items-center bg-white dark:bg-navy-700 lg:max-w-md">
        <div class="flex w-full max-w-sm grow flex-col justify-center p-5">
          <div class="text-center">
            <img class="mx-auto size-16 lg:hidden" src="{{ $appLogo }}" alt="{{ $appName }}" />
            <div class="mt-4">
              <h2 class="text-2xl font-semibold text-slate-600 dark:text-navy-100">{{ $title }}</h2>
              <p class="text-slate-400 dark:text-navy-300">{{ $description }}</p>
            </div>
          </div>
          <div class="mt-10">
            @if ($status)
              <div class="alert mb-3 flex rounded-lg border border-success px-2 py-2 text-success sm:px-5">
                {{ $status }}
              </div>
            @endif
            @if ($errors->any())
              <div class="alert mb-3 flex rounded-lg border border-warning px-2 py-2 text-warning sm:px-5">
                {{ $errors->first() }}
              </div>
            @endif

            {{ $slot }}
          </div>
        </div>
        <div class="my-5 flex justify-center text-xs text-slate-400 dark:text-navy-300">
          <a href="#">Privacy Notice</a>
          <div class="mx-3 my-1 w-px bg-slate-200 dark:bg-navy-500"></div>
          <a href="#">Term of service</a>
        </div>
      </main>
    </div>

    <script>
      document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
        var input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;

        button.addEventListener("click", function () {
          var isHidden = input.type === "password";
          input.type = isHidden ? "text" : "password";
          button.querySelector("[data-icon=show]").classList.toggle("hidden", isHidden);
          button.querySelector("[data-icon=hide]").classList.toggle("hidden", !isHidden);
          button.setAttribute("aria-pressed", isHidden ? "true" : "false");
          button.setAttribute("aria-label", isHidden ? "Sembunyikan password" : "Tampilkan password");
        });
      });
    </script>
  </body>
</html>
