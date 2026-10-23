<x-lineone menu="master">
  <div class="py-5">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
      <div>
        <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50">Hasil Pencarian</h2>
        <p class="mt-1 text-xs-plus text-slate-400 dark:text-navy-300">
          @if ($term !== '')
            Hasil untuk “{{ $term }}”
          @else
            Masukkan kata kunci pada kolom pencarian.
          @endif
        </p>
      </div>
    </div>

    <div class="mt-5 overflow-hidden rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-700 dark:bg-navy-750 dark:shadow-soft-dark">
      @forelse ($results as $result)
        <a href="{{ $result['url'] }}" class="group flex items-center gap-3 border-b border-slate-100 px-4 py-3 outline-hidden transition-colors last:border-b-0 hover:bg-slate-50 focus:bg-slate-50 dark:border-navy-600 dark:hover:bg-navy-700 dark:focus:bg-navy-700">
          <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $result['icon_class'] }}">
            {{ $result['icon'] }}
          </span>
          <span class="min-w-0 grow">
            <span class="block truncate font-medium text-slate-700 group-hover:text-slate-800 dark:text-navy-100 dark:group-hover:text-white">
              {{ $result['title'] }}
            </span>
            @if ($result['subtitle'] !== '')
              <span class="mt-0.5 block truncate text-xs text-slate-400 dark:text-navy-300">{{ $result['subtitle'] }}</span>
            @endif
          </span>
          <span class="shrink-0 text-tiny-plus text-slate-400 dark:text-navy-300">{{ $result['type'] }}</span>
        </a>
      @empty
        <div class="px-4 py-12 text-center">
          <p class="font-medium text-slate-700 dark:text-navy-100">Tidak ada hasil ditemukan</p>
          <p class="mt-1 text-xs-plus text-slate-400 dark:text-navy-300">Coba gunakan nama atau kata kunci yang berbeda.</p>
        </div>
      @endforelse
    </div>
  </div>
</x-lineone>
