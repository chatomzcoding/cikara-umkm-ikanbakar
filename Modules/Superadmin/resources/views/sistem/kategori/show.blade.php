<x-lineone menu="master">
    <x-header 
        title="Kategori"
        description="Detail Kategori - {{ $sistemkategori->nama }}"
        :links="[
            'Data Kategori' => route('superadmin.sistem.kategori.index')
        ]"
        ></x-header>
    <livewire:superadmin.sistem.kategori.show :uuid="$sistemkategori->uuid" />
</x-lineone>