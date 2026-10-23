<div
    class="is-scrollbar-hidden flex grow flex-col space-y-4 overflow-y-auto pt-6">
    <!-- Dashboard -->
    <a
    href="{{ route('dashboard') }}"
    data-tooltip="Dashboards"
    data-placement="right"
    class="tooltip-main-sidebar flex size-11 items-center justify-center rounded-lg @if ($menu == 'dashboard')
        bg-primary/10 text-primary
    @endif outline-hidden transition-colors duration-200 hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:bg-navy-600 dark:text-accent-light dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90"
    >
    <svg
        class="size-7"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
    >
        <path
        fill="currentColor"
        fill-opacity=".3"
        d="M5 14.059c0-1.01 0-1.514.222-1.945.221-.43.632-.724 1.453-1.31l4.163-2.974c.56-.4.842-.601 1.162-.601.32 0 .601.2 1.162.601l4.163 2.974c.821.586 1.232.88 1.453 1.31.222.43.222.935.222 1.945V19c0 .943 0 1.414-.293 1.707C18.414 21 17.943 21 17 21H7c-.943 0-1.414 0-1.707-.293C5 20.414 5 19.943 5 19v-4.94Z"
        />
        <path
        fill="currentColor"
        d="M3 12.387c0 .267 0 .4.084.441.084.041.19-.04.4-.204l7.288-5.669c.59-.459.885-.688 1.228-.688.343 0 .638.23 1.228.688l7.288 5.669c.21.163.316.245.4.204.084-.04.084-.174.084-.441v-.409c0-.48 0-.72-.102-.928-.101-.208-.291-.355-.67-.65l-7-5.445c-.59-.459-.885-.688-1.228-.688-.343 0-.638.23-1.228.688l-7 5.445c-.379.295-.569.442-.67.65-.102.208-.102.448-.102.928v.409Z"
        />
        <path
        fill="currentColor"
        d="M11.5 15.5h1A1.5 1.5 0 0 1 14 17v3.5h-4V17a1.5 1.5 0 0 1 1.5-1.5Z"
        />
        <path
        fill="currentColor"
        d="M17.5 5h-1a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5Z"
        />
    </svg>
    </a>

    @php
        $masterUrl = collect([
            'data-pokok.view' => route('superadmin.sistem.data-pokok.index'),
            'audit-logs.view' => route('superadmin.sistem.audit-log.index'),
            'error-pages.view' => route('superadmin.sistem.error-page.index'),
            'users.view' => route('superadmin.sistem.user.index'),
            'roles.view' => route('superadmin.sistem.role.index'),
            'categories.view' => route('superadmin.sistem.kategori.index'),
        ])->first(fn ($url, $permission) => auth()->user()->can($permission));
    @endphp

    @canany(['data-pokok.view', 'audit-logs.view', 'error-pages.view', 'users.view', 'roles.view', 'categories.view'])
        @if ($user->hasRole('superadmin'))
        <!-- Data -->
        <a
            href="{{ route('data.siswas.index') }}"
            data-tooltip="Data"
            data-placement="right"
            class="tooltip-main-sidebar flex size-11 items-center justify-center rounded-lg  @if ($menu == 'data')
                bg-primary/10 text-primary
            @endif outline-hidden transition-colors duration-200 hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25"
            >
            <svg
                class="size-7"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                fill-opacity="0.25"
                d="M21.0001 16.05V18.75C21.0001 20.1 20.1001 21 18.7501 21H6.6001C6.9691 21 7.3471 20.946 7.6981 20.829C7.7971 20.793 7.89609 20.757 7.99509 20.712C8.31009 20.586 8.61611 20.406 8.88611 20.172C8.96711 20.109 9.05711 20.028 9.13811 19.947L9.17409 19.911L15.2941 13.8H18.7501C20.1001 13.8 21.0001 14.7 21.0001 16.05Z"
                fill="currentColor"
                />
                <path
                fill-opacity="0.5"
                d="M17.7324 11.361L15.2934 13.8L9.17334 19.9111C9.80333 19.2631 10.1993 18.372 10.1993 17.4V8.70601L12.6384 6.26701C13.5924 5.31301 14.8704 5.31301 15.8244 6.26701L17.7324 8.17501C18.6864 9.12901 18.6864 10.407 17.7324 11.361Z"
                fill="currentColor"
                />
                <path
                d="M7.95 3H5.25C3.9 3 3 3.9 3 5.25V17.4C3 17.643 3.02699 17.886 3.07199 18.12C3.09899 18.237 3.12599 18.354 3.16199 18.471C3.20699 18.606 3.252 18.741 3.306 18.867C3.315 18.876 3.31501 18.885 3.31501 18.885C3.32401 18.885 3.32401 18.885 3.31501 18.894C3.44101 19.146 3.585 19.389 3.756 19.614C3.855 19.731 3.95401 19.839 4.05301 19.947C4.15201 20.055 4.26 20.145 4.377 20.235L4.38601 20.244C4.61101 20.415 4.854 20.559 5.106 20.685C5.115 20.676 5.11501 20.676 5.11501 20.685C5.25001 20.748 5.385 20.793 5.529 20.838C5.646 20.874 5.76301 20.901 5.88001 20.928C6.11401 20.973 6.357 21 6.6 21C6.969 21 7.347 20.946 7.698 20.829C7.797 20.793 7.89599 20.757 7.99499 20.712C8.30999 20.586 8.61601 20.406 8.88601 20.172C8.96701 20.109 9.05701 20.028 9.13801 19.947L9.17399 19.911C9.80399 19.263 10.2 18.372 10.2 17.4V5.25C10.2 3.9 9.3 3 7.95 3ZM6.6 18.75C5.853 18.75 5.25 18.147 5.25 17.4C5.25 16.653 5.853 16.05 6.6 16.05C7.347 16.05 7.95 16.653 7.95 17.4C7.95 18.147 7.347 18.75 6.6 18.75Z"
                fill="currentColor"
                />
            </svg>
        </a>
        @endif
    @endcanany
</div>


 <!-- Bottom Links -->
<div class="flex flex-col items-center space-y-3 py-3">
    <!-- Master -->
    @if ($masterUrl)
    <a
    href="{{ $masterUrl }}"
    class="flex size-11 items-center justify-center rounded-lg outline-hidden transition-colors duration-200 hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25"
    >
     <svg
                class="size-7"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                >
                <path
                d="M9.85714 3H4.14286C3.51167 3 3 3.51167 3 4.14286V9.85714C3 10.4883 3.51167 11 4.14286 11H9.85714C10.4883 11 11 10.4883 11 9.85714V4.14286C11 3.51167 10.4883 3 9.85714 3Z"
                fill="currentColor"
                />
                <path
                d="M9.85714 12.8999H4.14286C3.51167 12.8999 3 13.4116 3 14.0428V19.757C3 20.3882 3.51167 20.8999 4.14286 20.8999H9.85714C10.4883 20.8999 11 20.3882 11 19.757V14.0428C11 13.4116 10.4883 12.8999 9.85714 12.8999Z"
                fill="currentColor"
                fill-opacity="0.3"
                />
                <path
                d="M19.757 3H14.0428C13.4116 3 12.8999 3.51167 12.8999 4.14286V9.85714C12.8999 10.4883 13.4116 11 14.0428 11H19.757C20.3882 11 20.8999 10.4883 20.8999 9.85714V4.14286C20.8999 3.51167 20.3882 3 19.757 3Z"
                fill="currentColor"
                fill-opacity="0.3"
                />
                <path
                d="M19.757 12.8999H14.0428C13.4116 12.8999 12.8999 13.4116 12.8999 14.0428V19.757C12.8999 20.3882 13.4116 20.8999 14.0428 20.8999H19.757C20.3882 20.8999 20.8999 20.3882 20.8999 19.757V14.0428C20.8999 13.4116 20.3882 12.8999 19.757 12.8999Z"
                fill="currentColor"
                fill-opacity="0.3"
                />
            </svg>
    </a>
    @endif

    <!-- Profile -->
    <div id="profile-wrapper" class="flex">
    <button id="profile-ref" class="avatar cursor-pointer size-12">
        <img
        class="rounded-full"
        src="{{ asset($user->getPhoto()) }}"
        alt="avatar"
        />
        <span
        class="absolute right-0 size-3.5 rounded-full border-2 border-white bg-success dark:border-navy-700"
        ></span>
    </button>
    <div id="profile-box" class="popper-root fixed">
        <div
        class="popper-box w-64 rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-600 dark:bg-navy-700"
        >
        <div
            class="flex items-center space-x-4 rounded-t-lg bg-slate-100 py-5 px-4 dark:bg-navy-800"
        >
            <div class="avatar size-14">
            <img
                class="rounded-full"
                src="{{ $user->getPhoto()}}"
                alt="avatar"
            />
            </div>
            <div>
            <a
                href="#"
                class="text-base font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light"
            >
                {{ $user->name }}
            </a>
            <p class="text-xs text-slate-400 dark:text-navy-300">
                {{ $user->roles()->first()->display_name ?? null }}
            </p>
            </div>
        </div>
        <div class="flex flex-col pt-2 pb-5">
            <a
            href="{{ route('user.settings') }}"
            class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-hidden transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600"
            >
            <div
                class="flex size-8 items-center justify-center rounded-lg bg-warning text-white"
            >
                <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-4.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
                </svg>
            </div>

            <div>
                <h2
                class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary dark:text-navy-100 dark:group-hover:text-accent-light dark:group-focus:text-accent-light"
                >
                Profil
                </h2>
                <div
                class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300"
                >
                Pengaturan Akun
                </div>
            </div>
            </a>
            @if ($user->hasRole('superadmin'))
                <a
                href="{{ route('superadmin.sistem.user.index') }}"
                class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-hidden transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600"
                >
                <div
                    class="flex size-8 items-center justify-center rounded-lg bg-secondary text-white"
                >
                    <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-4.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                    >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                    />
                    </svg>
                </div>

                <div>
                    <h2
                    class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary dark:text-navy-100 dark:group-hover:text-accent-light dark:group-focus:text-accent-light"
                    >
                    User
                    </h2>
                    <div
                    class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300"
                    >
                    Daftar User
                    </div>
                </div>
                </a>
                <a
                href="{{ route('superadmin.sistem.audit-log.index') }}"
                class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-hidden transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600"
                >
                <div
                    class="flex size-8 items-center justify-center rounded-lg bg-error text-white"
                >
                    <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-4.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                    >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                    </svg>
                </div>

                <div>
                    <h2
                    class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary dark:text-navy-100 dark:group-hover:text-accent-light dark:group-focus:text-accent-light"
                    >
                    Aktifitas
                    </h2>
                    <div
                    class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300"
                    >
                    Kelola aktifitas Sistem
                    </div>
                </div>
                </a>
                <a
                href="{{ route('superadmin.sistem.data-pokok.index') }}"
                class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-hidden transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600"
                >
                <div
                    class="flex size-8 items-center justify-center rounded-lg bg-success text-white"
                >
                    <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-4.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                    >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    </svg>
                </div>

                <div>
                    <h2
                    class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary dark:text-navy-100 dark:group-hover:text-accent-light dark:group-focus:text-accent-light"
                    >
                    Pengaturan
                    </h2>
                    <div
                    class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300"
                    >
                    Kelola Data Pokok
                    </div>
                </div>
                </a>
            @endif
            <div class="mt-3 px-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                class="btn h-9 w-full space-x-2 bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90"
                >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                    />
                </svg>
                <span>Keluar</span>
                </button>
            </form>
            </div>
        </div>
        </div>
    </div>
    </div>
</div>
