<?php

use App\Livewire\Concerns\WithCrudTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\Livewire;

class CrudTableResetTestComponent extends Component
{
    use WithCrudTable;

    protected function crudModelClass(): string
    {
        return User::class;
    }

    protected function crudFilteredQuery(): Builder
    {
        return User::query();
    }

    protected function crudSortableFields(): array
    {
        return ['name', 'email'];
    }

    protected function crudDefaultSortField(): string
    {
        return 'email';
    }

    protected function crudExportColumns(): array
    {
        return [];
    }

    protected function crudExportPermission(): string
    {
        return '';
    }

    public function render(): string
    {
        return '<div></div>';
    }
}

test('reset tabel mengembalikan sorting ke field default CRUD yang valid', function () {
    Livewire::test(CrudTableResetTestComponent::class)
        ->set('search', 'contoh')
        ->set('perPage', 50)
        ->set('sortField', 'name')
        ->set('sortDirection', 'asc')
        ->call('resetCrudTable')
        ->assertSet('search', '')
        ->assertSet('perPage', 10)
        ->assertSet('sortField', 'email')
        ->assertSet('sortDirection', 'desc');
});
