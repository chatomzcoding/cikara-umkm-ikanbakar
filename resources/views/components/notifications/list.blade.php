@forelse ($items as $notification)
    @php
        $severity = $notification->data['severity'] ?? 'primary';
        $style = match ($severity) {
            'danger', 'error' => ['bg' => 'bg-error/10 dark:bg-error/15', 'text' => 'text-error'],
            'warning' => ['bg' => 'bg-warning/10 dark:bg-warning/15', 'text' => 'text-warning'],
            'success' => ['bg' => 'bg-success/10 dark:bg-success/15', 'text' => 'text-success'],
            'info' => ['bg' => 'bg-info/10 dark:bg-info/15', 'text' => 'text-info'],
            default => ['bg' => 'bg-primary/10 dark:bg-accent-light/15', 'text' => 'text-primary dark:text-accent-light'],
        };
    @endphp

    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
        @csrf
        @method('PATCH')
        <button
            type="submit"
            class="relative flex w-full items-start space-x-3 rounded-lg p-2 text-left transition-colors hover:bg-slate-100 dark:hover:bg-navy-600 {{ $notification->read_at ? '' : 'bg-slate-50 dark:bg-navy-750' }}"
            title="{{ $notification->data['title'] ?? 'Notifikasi' }}"
        >
            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $style['bg'] }}">
                <i class="{{ $notification->data['icon'] ?? 'fa-solid fa-bell' }} {{ $style['text'] }}"></i>
            </span>

            <span class="min-w-0 flex-1">
                <span class="block truncate font-medium text-slate-700 dark:text-navy-100">
                    {{ $notification->data['title'] ?? 'Notifikasi' }}
                </span>
                <span class="mt-0.5 block text-xs text-slate-500 line-clamp-2 dark:text-navy-300">
                    {{ $notification->data['message'] ?? '' }}
                </span>
                <span class="mt-1 block text-[11px] text-slate-400 dark:text-navy-400">
                    {{ $notification->created_at->diffForHumans() }}
                </span>
            </span>

            @if (! $notification->read_at)
                <span class="mt-1 size-2 shrink-0 rounded-full bg-secondary" aria-label="Belum dibaca"></span>
            @endif
        </button>
    </form>
@empty
    <div class="py-8 text-center">
        <img
            class="mx-auto w-28"
            src="{{ asset('img/illustrations/empty-girl-box.svg') }}"
            alt="Tidak ada notifikasi"
        />
        <p class="mt-4 font-semibold text-slate-700 dark:text-navy-100">Belum ada notifikasi</p>
        <p class="mt-1 text-sm text-slate-400 dark:text-navy-300">Notifikasi baru akan muncul di sini.</p>
    </div>
@endforelse
