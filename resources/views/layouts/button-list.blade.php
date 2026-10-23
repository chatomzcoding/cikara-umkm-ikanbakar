<div class="table-search-wrapper flex items-center gap-1 ml-auto">
    <label class="block" wire:ignore>
        <input wire:model.live='search'
            class="table-search-input form-input w-0 bg-transparent px-1 text-right transition-all duration-100 placeholder:text-slate-500 dark:placeholder:text-navy-200"
            placeholder="Cari disini..." type="text" />
    </label>
    <button
        data-tooltip="Kolom Pencarian"
        class="table-search-toggle btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </button>
    @switch($pagination)
        @case(10)
            <button wire:click="$set('pagination',25)"
                data-tooltip="Tampilkan 25 Data"        
                class="btn rounded-full size-8 p-0 text-slate-600 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 8h1v8" /><path d="M14 10v4a2 2 0 1 0 4 0v-4a2 2 0 1 0 -4 0" /></svg>
            </button>
            @break
        @case(25)
            <button wire:click="$set('pagination',50)"
                data-tooltip="Tampilkan 50 Data"        
                class="btn size-8 rounded-full p-0 text-slate-600 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 15a1 1 0 0 0 1 1h2a1 1 0 0 0 1 -1v-2a1 1 0 0 0 -1 -1h-3v-4h4" /><path d="M6 8h3a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-2a1 1 0 0 0 -1 1v2a1 1 0 0 0 1 1h3" /></svg>
            </button>
            @break
        @case(50)
            <button wire:click="$set('pagination',100)"
                data-tooltip="Tampilkan 100 Data"        
                class="btn size-8 rounded-full p-0 text-slate-600 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 10v4a2 2 0 1 0 4 0v-4a2 2 0 1 0 -4 0" /><path d="M6 15a1 1 0 0 0 1 1h2a1 1 0 0 0 1 -1v-2a1 1 0 0 0 -1 -1h-3v-4h4" /></svg>
            </button>
            @break
        @case(100)
            <button wire:click="$set('pagination',5)"
                data-tooltip="Tampilkan 5 Data"        
                class="btn size-8 rounded-full p-0 text-slate-600 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 8h1v8" /><path d="M9 10v4a2 2 0 1 0 4 0v-4a2 2 0 1 0 -4 0" /><path d="M16 10v4a2 2 0 1 0 4 0v-4a2 2 0 1 0 -4 0" /></svg>
            </button>
            @break
        @default
             <button wire:click="$set('pagination',10)"
                data-tooltip="Tampilkan 10 Data"        
                class="btn size-8 rounded-full p-0 text-slate-600 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 15a1 1 0 0 0 1 1h2a1 1 0 0 0 1 -1v-2a1 1 0 0 0 -1 -1h-3v-4h4" /></svg>
            </button>
    @endswitch
    <button
        data-tooltip="Urutkan Data"
        wire:click="$toggle('sort')"
        class="btn size-8 rounded-full p-0 text-slate-600 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
        @if ($sort)
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 21v-5c0 -1.38 .62 -2 2 -2s2 .62 2 2v5m0 -3h-4" /><path d="M19 10h-4l4 -7h-4" /><path d="M4 15l3 3l3 -3" /><path d="M7 6v12" /></svg>
        @else
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 10v-5c0 -1.38 .62 -2 2 -2s2 .62 2 2v5m0 -3h-4" /><path d="M19 21h-4l4 -7h-4" /><path d="M4 15l3 3l3 -3" /><path d="M7 6v12" /></svg>
        @endif
    </button>

    <button
        data-tooltip="Reset Filter"
        wire:click='buttonResetFilter'
        class="btn size-8 rounded-full p-0 hover:bg-slate-150 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" class="size-4.5">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M4 6h16" />
            <path d="M6 12h12" />
            <path d="M9 18h4" />
            <path d="M22 22l-5 -5m0 5l5 -5" />
        </svg>
    </button>
    <button
        x-tooltip="'Perbaharui Halaman'"
        wire:click='$refresh'
        class="btn size-8 rounded-full p-0 hover:bg-slate-150 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" class="size-4.5">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
        </svg>
    </button>
</div>