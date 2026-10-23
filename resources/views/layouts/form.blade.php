 <div class="card">
    <div class="items-center justify-between flex flex-col space-y-4 border-b border-slate-200 p-4 dark:border-navy-500 sm:flex-row sm:justify-between sm:space-y-0 sm:px-5">
        <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
            Form
        </h2>
        <div class="flex items-center space-x-2">
            <button
                class="btn size-8 rounded-full bg-info/10 p-0 font-medium text-info hover:bg-info/20 focus:bg-info/20 active:bg-info/25"
                @click="openForm = false"
                wire:click="$set('leftComponent',false)"
                >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" class="size-5">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M18 6l-12 12" />
                    <path d="M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
    <section class="p-4 sm:p-5">
        @if ($leftComponent)
            <livewire:dynamic-component
                :component="$leftComponent"
                :id="$id"
                :key="$leftComponent.'-'.$id"
            />
        @endif
    </section>
</div>