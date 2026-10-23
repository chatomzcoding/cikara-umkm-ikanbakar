@props(['ids', 'selected' => []])

@php
    $normalizedIds = collect($ids)->map(fn ($id) => (string) $id)->values();
    $normalizedSelected = collect($selected)->map(fn ($id) => (string) $id);
    $allSelected = $normalizedIds->isNotEmpty() && $normalizedIds->diff($normalizedSelected)->isEmpty();
@endphp

<input
    type="checkbox"
    wire:click="togglePageSelection({{ $normalizedIds->toJson() }})"
    @checked($allSelected)
    class="size-4 cursor-pointer rounded border-slate-400 accent-indigo-600"
    aria-label="Pilih semua data pada halaman ini"
>
