@props(['field', 'label', 'align' => 'left', 'sortField' => null, 'sortDirection' => 'asc'])

<th {{ $attributes->class(['whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100', 'text-center' => $align === 'center']) }}>
    <button type="button" wire:click="sortBy('{{ $field }}')" class="inline-flex items-center gap-1 uppercase">
        {{ $label }}
        @if ($sortField === $field)
            <span class="text-[10px]" aria-label="Urutan {{ $sortDirection === 'asc' ? 'naik' : 'turun' }}">
                {{ $sortDirection === 'asc' ? 'ASC' : 'DESC' }}
            </span>
        @endif
    </button>
</th>
