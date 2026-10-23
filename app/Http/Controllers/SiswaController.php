<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Support\Facades\Gate;

class SiswaController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Siswa::class);

        return view('crud.siswas.index');
    }
}
