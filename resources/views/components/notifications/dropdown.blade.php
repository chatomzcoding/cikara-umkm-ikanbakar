<div id="notification-wrapper" class="flex">
    <button
        id="notification-ref"
        type="button"
        class="btn relative size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25"
        aria-label="Buka notifikasi"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-slate-500 dark:text-navy-100" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.375 17.556h-6.75m6.75 0H21l-1.58-1.562a2.254 2.254 0 01-.67-1.596v-3.51a6.612 6.612 0 00-1.238-3.85 6.744 6.744 0 00-3.262-2.437v-.379c0-.59-.237-1.154-.659-1.571A2.265 2.265 0 0012 2c-.597 0-1.169.234-1.591.65a2.208 2.208 0 00-.659 1.572v.38c-2.621.915-4.5 3.385-4.5 6.287v3.51c0 .598-.24 1.172-.67 1.595L3 17.556h12.375zm0 0v1.11c0 .885-.356 1.733-.989 2.358A3.397 3.397 0 0112 22a3.397 3.397 0 01-2.386-.976 3.313 3.313 0 01-.989-2.357v-1.111h6.75z" />
        </svg>

        @if ($unreadNotificationCount > 0)
              <span
                    class="absolute -top-px -right-px flex size-3 items-center justify-center"
                  >
                    <span
                      class="absolute inline-flex h-full w-full animate-ping rounded-full bg-secondary opacity-80"
                    ></span>
                    <span
                      class="inline-flex size-2 rounded-full bg-secondary"
                    ></span>
                  </span>
        @endif
    </button>

    <div id="notification-box" class="popper-root">
        <div class="notification-tab-wrapper popper-box mx-4 mt-1 flex max-h-[calc(100vh-6rem)] w-[calc(100vw-2rem)] flex-col rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-800 dark:bg-navy-700 dark:shadow-soft-dark sm:m-0 sm:w-80">
            <div class="rounded-t-lg bg-slate-100 text-slate-600 dark:bg-navy-800 dark:text-navy-200">
                <div class="flex items-center justify-between px-4 pt-3">
                    <div class="flex items-center space-x-2">
                        <h3 class="font-medium text-slate-700 dark:text-navy-100">Notifikasi</h3>
                        @if ($unreadNotificationCount > 0)
                            <span class="badge h-4 min-w-4 rounded-full bg-primary/10 px-1 text-[10px] leading-4 text-primary dark:bg-accent-light/15 dark:text-accent-light" title="{{ $unreadNotificationCount }} notifikasi belum dibaca">
                                {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                            </span>
                        @endif
                    </div>

                    @if ($unreadNotificationCount > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn h-7 rounded-md px-2 text-xs hover:bg-slate-300/20 dark:hover:bg-navy-300/20" title="Tandai semua sudah dibaca">
                                Baca semua
                            </button>
                        </form>
                    @endif
                </div>

                <div class="tabs is-scrollbar-hidden flex shrink-0 overflow-x-auto px-2 text-xs">
                    @foreach ([
                        'all' => 'Semua',
                        'alert' => 'Peringatan',
                        'event' => 'Agenda',
                        'activity' => 'Aktivitas',
                    ] as $key => $label)
                        <button
                            type="button"
                            class="tab btn shrink-0 rounded-none border-b-2 px-2.5 py-2.5"
                            data-active-class="border-primary dark:border-accent text-primary dark:text-accent-light"
                            data-default-class="border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100"
                            data-target="#notification-{{ $key }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col overflow-hidden">
                @foreach (['all', 'alert', 'event', 'activity'] as $key)
                    <div id="notification-{{ $key }}" class="tab-content tab-shift-left is-scrollbar-hidden space-y-1 overflow-y-auto px-2 py-2">
                        @include('components.notifications.list', ['items' => $notificationGroups[$key]])
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
