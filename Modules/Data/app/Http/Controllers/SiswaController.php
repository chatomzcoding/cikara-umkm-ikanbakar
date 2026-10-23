<?php

namespace Modules\Data\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Data\Models\Siswa;

class SiswaController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Siswa::class);

        return view('data::siswas.index');
    }
}
