<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Superadmin\Models\Sistemkategori;

class SistemkategoriController extends Controller
{
    public function index()
    {
        return view('superadmin::sistem.kategori.index');
    }

    public function show($id)
    {
        $sistemkategori = Sistemkategori::where('uuid', $id)->first();

        return view('superadmin::sistem.kategori.show', compact('sistemkategori'));
    }
}
