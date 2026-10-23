@props(['id'])

<input
    type="checkbox"
    wire:model.live="selected"
    value="{{ (string) $id }}"
    class="size-4 cursor-pointer rounded border-slate-400 accent-indigo-600"
    aria-label="Pilih data"
>
